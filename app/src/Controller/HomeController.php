<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/alwaysAccessible', name: 'always_accessible')]
    public function alwaysAccessible(): Response
    {
        return new Response('This page is accessible for any user any time');
    }

    #[Route('/cacheable', name: 'cacheable')]
    public function cacheable(): Response
    {
        $response = new Response('This page is the same for any user');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
