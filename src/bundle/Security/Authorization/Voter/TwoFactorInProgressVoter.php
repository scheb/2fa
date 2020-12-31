<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization\Voter;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @final
 */
class TwoFactorInProgressVoter implements VoterInterface
{
    public const IS_AUTHENTICATED_2FA_IN_PROGRESS = 'IS_AUTHENTICATED_2FA_IN_PROGRESS';

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!($token instanceof TwoFactorTokenInterface)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (self::IS_AUTHENTICATED_2FA_IN_PROGRESS === $attribute) {
                return VoterInterface::ACCESS_GRANTED;
            }
            if (AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY === $attribute) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
