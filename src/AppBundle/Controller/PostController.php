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
	}