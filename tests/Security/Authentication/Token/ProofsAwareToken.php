<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * A token of Symfony 8.2, where these methods are declared on the interface.
 */
class ProofsAwareToken extends UsernamePasswordToken
{
    /** @var array<string, int> */
    private array $proofs = [];

    /** @return array<string, int> */
    public function getAuthenticationProofs(): array
    {
        return $this->proofs;
    }

    /** @param array<string, int> $proofs */
    public function setAuthenticationProofs(array $proofs): void
    {
        $this->proofs = $proofs;
    }
}
