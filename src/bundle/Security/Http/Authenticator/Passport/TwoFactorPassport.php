<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @final
 */
class TwoFactorPassport extends Passport
{
    /**
     * @var TwoFactorTokenInterface
     */
    private $twoFactorToken;

    public function __construct(TwoFactorTokenInterface $twoFactorToken, CredentialsInterface $credentials, array $badges = [])
    {
        parent::__construct(new UserBadge($twoFactorToken->getUserIdentifier()), $credentials, $badges);
        $this->twoFactorToken = $twoFactorToken;
    }

    public function getTwoFactorToken(): TwoFactorTokenInterface
    {
        return $this->twoFactorToken;
    }
}
