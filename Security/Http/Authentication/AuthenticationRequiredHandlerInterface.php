<?php

namespace Scheb\TwoFactorBundle\Security\Http\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface AuthenticationRequiredHandlerInterface
{
    /**
     * Redirect to the two-factor authentication form.
     *
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return Response
     */
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response;
}
