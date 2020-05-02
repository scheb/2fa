<?php

declare(strict_types=1);

namespace App\Controller;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MembersController extends AbstractController
{
    /**
     * @Route("/members", name="members_area")
     */
    public function membersArea(TokenStorageInterface $tokenStorage, GoogleAuthenticator $googleAuthenticator, TotpAuthenticator $totpAuthenticator)
    {
        $user = $tokenStorage->getToken()->getUser();

        return $this->render('members/index.html.twig', [
            'qrCodeGa' => $googleAuthenticator->getQRContent($user),
            'qrCodeTotp' => $totpAuthenticator->getQRContent($user),
        ]);
    }
}
