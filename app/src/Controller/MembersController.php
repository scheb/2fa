<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\TwoFactorFormData;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/members/validators', name: 'validators_form')]
    public function validators(Request $request): Response
    {
        $formData = new TwoFactorFormData();
        $form = $this->createFormBuilder($formData)
            ->add('googleTotpCode')
            ->add('totpCode')
            ->getForm();

        $isValid = null;
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $isValid = $form->isValid();
        }

        return $this->render('members/validators.html.twig', [
            'form' => $form,
            'isValid' => $isValid,
        ]);
    }
}
