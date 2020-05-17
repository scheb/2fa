<?php

declare(strict_types=1);

namespace App\Controller;

use Endroid\QrCode\QrCode;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MembersController extends AbstractController
{
    /**
     * @Route("/members", name="members_area")
     */
    public function membersArea(TokenStorageInterface $tokenStorage)
    {
        $user = $tokenStorage->getToken()->getUser();

        return $this->render('members/index.html.twig', [
            'displayQrCodeGa' => $user instanceof GoogleAuthenticatorTwoFactorInterface,
            'displayQrCodeTotp' => $user instanceof TotpTwoFactorInterface,
        ]);
    }

    /**
     * @Route("/members/qr/ga", name="qr_code_ga")
     */
    public function displayGoogleAuthenticatorQrCode(TokenStorageInterface $tokenStorage, QrCodeGenerator $qrCodeGenerator)
    {
        $user = $tokenStorage->getToken()->getUser();
        if (!($user instanceof GoogleAuthenticatorTwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        return $this->displayQrCode($qrCodeGenerator->getGoogleAuthenticatorQrCode($user));
    }

    /**
     * @Route("/members/qr/totp", name="qr_code_totp")
     */
    public function displayTotpQrCode(TokenStorageInterface $tokenStorage, QrCodeGenerator $qrCodeGenerator)
    {
        $user = $tokenStorage->getToken()->getUser();
        if (!($user instanceof TotpTwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        return $this->displayQrCode($qrCodeGenerator->getTotpQrCode($user));
    }

    private function displayQrCode(QrCode $qrCode): Response
    {
        $qrCode->setWriterByName('png');
        $qrCode->setEncoding('UTF-8');
        $qrCode->setSize(200);
        $qrCode->setRoundBlockSize(true);
        $qrCode->setMargin(0);

        return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
    }
}
