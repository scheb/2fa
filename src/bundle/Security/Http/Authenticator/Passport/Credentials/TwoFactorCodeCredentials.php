<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials;

use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;

/**
 * @final
 */
class TwoFactorCodeCredentials implements CredentialsInterface
{
    /**
     * @var string|null
     */
    private $code;

    /**
     * @var bool
     */
    private $resolved = false;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        if (null === $this->code) {
            throw new \LogicException('The credentials are erased as another listener already verified these credentials.');
        }

        return $this->code;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
        $this->code = null;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
