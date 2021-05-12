<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport;

use RuntimeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;

/**
 * @final
 */
class TwoFactorPassport implements UserPassportInterface
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
        return $this->twoFactorToken->getProviderKey(true);
    }

    public function getUser(): UserInterface
    {
        $user = $this->twoFactorToken->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        // This case should never happen as the Passport is only used with the authenticator-based security system and
        // with that system there is no string-like user.
        throw new RuntimeException('Failed to find User of type UserInterface');
    }
}
