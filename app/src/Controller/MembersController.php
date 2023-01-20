<?php

declare(strict_types=1);

namespace App\Controller;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MembersController extends AbstractController
{
    #[Route('/members', name: 'members_area')]
    public function membersArea(TokenStorageInterface $tokenStorage): Response
    {
        $user = $tokenStorage->getToken()->getUser();

        return $this->render('members/index.html.twig', [
            'displayQrCodeGa' => $user instanceof GoogleAuthenticatorTwoFactorInterface && $user->isGoogleAuthenticatorEnabled(),
            'displayQrCodeTotp' => $user instanceof TotpTwoFactorInterface && $user->isTotpAuthenticationEnabled(),
        ]);
    }
}
