<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="_security_login")
     */
    public function login(AuthenticationUtils $authUtils): Response
    {
        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/password", name="_security_password")
     */
    public function register(UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $plainPassword = 'test';
        $encoded = $hasher->hashPassword($user, $plainPassword);

        return new Response($encoded);
    }

    /**
     * @Route("/2fa/inProgress", name="2fa_in_progress")
     */
    public function accessibleDuring2fa(): Response
    {
        return new Response('This page is accessible during 2fa');
    }
}
