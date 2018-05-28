<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Form\PostType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }
    
    /**
     * @Route("/posts", name="posts")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postsAction(Request $request)
    {
        $postForm = $this->createForm(PostType::class, new Post());
        $posts = $this->getDoctrine()->getRepository('AppBundle:Post')->findAll();
        
        return $this->render('default/posts.html.twig', [
            'postForm' => $postForm->createView(),
            'posts' => $posts
        ]);
    }
}
