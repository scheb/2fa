<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportTrait;

class TwoFactorPassport implements PassportInterface
{
    use PassportTrait;

    /**
     * @var TwoFactorTokenInterface
     */
    private $twoFactorToken;

    public function __construct(TwoFactorTokenInterface $twoFactorToken, CredentialsInterface $credentials, array $badges)
    {
        $this->twoFactorToken = $twoFactorToken;
        $this->addBadge($credentials);
        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    public function getTwoFactorToken(): TwoFactorTokenInterface
    {
        return $this->twoFactorToken;
    }

    public function getFirewallName(): string
    {
        return $this->twoFactorToken->getProviderKey();
    }
}
