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