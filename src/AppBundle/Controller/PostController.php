<?php
	namespace AppBundle\Controller;

	use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use FOS\RestBundle\Controller\Annotations as Rest;
	use FOS\RestBundle\Controller\FOSRestController;
	use Nelmio\ApiDocBundle\Annotation\ApiDoc;
	use AppBundle\Form\PostType;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use Symfony\Component\HttpKernel\Exception\HttpException;
	use FOS\RestBundle\View\View;
	use AppBundle\Entity\Post;
	use Psr\Cache\CacheItemPoolInterface;

	class PostController extends FOSRestController
	{
		private static $DEFAULT_POSTS_COUNT = 10;
		private static $POSTS_CACHE_DURATION = 3600;

		/**
	     * @var CacheItemPoolInterface
	     */
		private $cache = null;

		public function __construct(CacheItemPoolInterface $cache){
			$this->cache = $cache;
		}

		/**
	     * @Route("/posts", name="posts")
	     * 
	     * @param Request $request
	     *
	     * @return \Symfony\Component\HttpFoundation\Response
	     */
	    public function postsAction(Request $request)
	    {
	        $postForm = $this->createForm(PostType::class, new Post());
	        $posts = $this->getPostsAction($request);


	        $totalPostsReturned = $posts['returnedCount']; //returned posts count now
	        $totalPosts = $posts['allCount']; //all posts count
	        $limit = $posts['count']; //per page
	        $maxPages = ceil($totalPosts / $limit);
	        $thisPage = 1;
	        if($request->get('p'))
			{
				$page = (int)$request->get('p');
				if($page > 0)
					$thisPage = $page;
			}
	        return $this->render('post/list.html.twig', [
	            'postForm' => $postForm->createView(),
	            'posts' => $posts['posts'],
	            'maxPages' => $maxPages,
	            'thisPage' => $thisPage
	        ]);
	    }

	    /**
	     * @Route("/post/{id}", name="post")
	     *
	     * @return \Symfony\Component\HttpFoundation\Response
	     */
	    public function postAction($id)
	    {
	    	$post = $this->getPostAction($id);
	    	if (!$post) {
				throw new NotFoundHttpException(sprintf('Post \'%s\' was not found.', $id));
			}
			$owner = 0;
			if ($post->getAuthor() == $this->get('security.token_storage')->getToken()->getUser()) {
				$owner = 1;
			}
	    	$csrf = $this->get('security.csrf.token_manager');
	    	$postForm = $this->createForm(PostType::class, new Post());
			$intention = $postForm->getName();
			$token = $csrf->getToken($intention)->getValue();

	        return $this->render('post/show.html.twig', [
	            'post' => $post,
	            'csrf_token' => $token,
	            'owner' => $owner
	        ]);
	    }

	    /**
		 * @Rest\Post("/api/post/{id}") 
		*/
		public function editPostAction($id, Request $request) {
			$id = (float)($id);
			if(!($id > 0))
			{
				throw new NotFoundHttpException(sprintf('Wrong id: (%s)', $id));
			}

			$post = $this->getPostAction($id);

			if ($post->getAuthor() != $this->get('security.token_storage')->getToken()->getUser()) {
				throw new NotFoundHttpException(sprintf('No such post (2) (%s)', $id));
			}

			if(!isset($post))
			{
				throw new NotFoundHttpException(sprintf('No such post (%s)', $id));
			}
			try 
	        {
	        	$form = $this->createForm(PostType::class);
	            $form->handleRequest($request);
	            if ($form->isValid()) {
	            	$data = $form->getData();
					$em = $this->getDoctrine()->getManager();
					$em->getConnection()->beginTransaction();

					$post->setContent($data->getContent());
					
					$em->merge($post);
					$em->flush();
					$em->getConnection()->commit();
		            $this->cache->invalidateTags(['posts']);
		            return array('post' => $post);
		        }
		        else {
		        	throw new HttpException(500, "Wrong post data");
		        }
	        }
	        catch(Exception $e)
	        {
	            $em->getConnection()->rollBack();
	            throw $e;
	        }
		}

		 /**
		 * @Rest\Delete("/api/post/{id}") 
		*/
		public function removePostAction($id, Request $request) {
			$id = (float)($id);
			if(!($id > 0))
			{
				throw new NotFoundHttpException(sprintf('Wrong id: (%s)', $id));
			}

			$post = $this->getPostAction($id);

			if ($post->getAuthor() != $this->get('security.token_storage')->getToken()->getUser()) {
				throw new NotFoundHttpException(sprintf('No such post (2) (%s)', $id));
			}

			if(!isset($post))
			{
				throw new NotFoundHttpException(sprintf('No such post (%s)', $id));
			}
			$em = $this->getDoctrine()->getManager();
			$em->getConnection()->beginTransaction();
			try 
	        {
				$em->remove($post);
				$em->flush();
				$em->getConnection()->commit();
	            $this->cache->invalidateTags(['posts']);
	            return array('success' => true);
	        }
	        catch(Exception $e)
	        {
	            $em->getConnection()->rollBack();
	            throw $e;
	        }
		}

		/**
		 * @Rest\Get("/api/post/{id}") 
		*/
		public function getPostAction($id) {
			$postsRepository = $this->getDoctrine()->getRepository('AppBundle:Post');
			$post = NULL;
			try {
				$post = $postsRepository->find($id);
			} catch (\Exception $exception) {
				$post = NULL;
			}

			if (!$post) {
				throw new NotFoundHttpException(sprintf('Post \'%s\' was not found.', $id));
			}
			return $post;
		}

		/**
		 * @Rest\Post("/api/posts") 
		*/
		public function addPostAction(Request $request) {
			$em = $this->getDoctrine()->getManager();
	        $em->getConnection()->beginTransaction();
	        $newPost = new Post();
	        try 
	        {
	            $form = $this->createForm(PostType::class, $newPost);

	            $form->handleRequest($request);
	            if ($form->isSubmitted() && $form->isValid()) {
	                $data = $form->getData();

	                $newPost->setAuthor($this->container->get('security.token_storage')->getToken()->getUser());
	                $em = $this->getDoctrine()->getManager();
	                $em->persist($newPost);
	                $em->flush();
	                $em->getConnection()->commit();

	                $this->cache->invalidateTags(['posts']);
	                return array('post' => $newPost);
	            }
	        }
	        catch(Exception $e)
	        {
	            $em->getConnection()->rollBack();
	            throw $e;
	        }
		}

		/**
		 * @Rest\Get("/api/posts") 
		*/
		public function getPostsAction(Request $request) {
			
			
			$params = $this->getPageParams($request);

			$offset = $params['offset'];
			$count = $params['count'];

			$cache_key = 'posts-'.$offset.'_'.$count;
			$cacheItem = $this->cache->getItem($cache_key);

			if($cacheItem->isHit())
	    	{
	    		return $cacheItem->get();
	    	}

	    	$postsRepository = $this->getDoctrine()->getRepository('AppBundle:Post');
			$post = NULL;
			try {
				$posts = $postsRepository->findBy(array(),array('id' => 'DESC'),$count,$offset);
			} catch (\Exception $exception) {
				$posts = NULL;
			}

			$result = array(
				'posts' => $posts,
				'allCount' => $postsRepository->count(),
				'offset' => $offset,
				'count' => $count,
				'returnedCount' => $count
			);
			if($result['allCount'] < $result['returnedCount'])
	        	$result['returnedCount'] = $result['allCount'];
			$cacheItem->expiresAfter(self::$POSTS_CACHE_DURATION);
			$cacheItem->set($result);
			$cacheItem->tag('posts');

			$this->cache->save($cacheItem);
			return $result;
		}

		/**
		 * This method controls paging and return offset/count numbers based on request
		 */
		private function getPageParams(Request $request): Array
		{
			$page = 1;
			$offset = 0;
			$count = self::$DEFAULT_POSTS_COUNT;

			if($request->get('p'))
			{
				$page = (int)$request->get('p');
				$on_page_count = self::$DEFAULT_POSTS_COUNT;
				if($request->get('op_cnt'))
					$on_page_count = (int)$request->get('op_cnt');

				if($page > 0 && $on_page_count > 0)
				{
					$offset = ($page-1)*$on_page_count;
					$count = $on_page_count;
				}
			}
			else
			{
				if($request->get('offset'))
					$offset = (int)$request->get('offset');
				if($request->get('count'))
					$count = (int)$request->get('count');
				if(!($offset > 0))
					$offset = 0;
				if(!($count > 0))
					$count = self::$DEFAULT_POSTS_COUNT;
			}

			return array(
				'offset' => $offset,
				'count' => $count
			);
		}
	}