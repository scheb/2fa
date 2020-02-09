<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Christian Scheb
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Event;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationSuccessEventSuppressor
{
    /**
     * @var string
     */
    private $firewallName;

    public function __construct(string $firewallName)
    {
        $this->firewallName = $firewallName;
    }

    public function onLogin(AuthenticationEvent $event): void
    {
        $token = $event->getAuthenticationToken();

        // We have a TwoFactorToken, make sure the security.authentication.success is not propagated to other
        // listeners, since we do not have a successful login (yet)
        if ($this->isTwoFactorTokenAndFirewall($token)) {
            $event->stopPropagation();
        }
    }

    private function isTwoFactorTokenAndFirewall(TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface && $token->getProviderKey() === $this->firewallName;
    }
}
