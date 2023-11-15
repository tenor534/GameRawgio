<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


class HelloController extends AbstractController
{
   // #[Route('/hello', name: 'app_hello')]
    public function index_(): Response
    {
        return $this->render('hello/index.html.twig', [
            'controller_name' => 'HelloController',
        ]);
    }

    /**
     * @Route("/hello", name="hello_world")
     */
    #[Route('/hello', name: 'hello_world')]
    public function index(): JsonResponse
    {
        $data = [
            'hello' => 'world'
        ];
       
        $jd = new JsonResponse($data);
       
        //dd($jd);
        
        return new JsonResponse($data);
       
    }
}
