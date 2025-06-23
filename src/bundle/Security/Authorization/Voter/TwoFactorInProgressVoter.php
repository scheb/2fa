<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authorization\Voter;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @final
 */
class TwoFactorInProgressVoter implements CacheableVoterInterface
{
    public const IS_AUTHENTICATED_2FA_IN_PROGRESS = 'IS_AUTHENTICATED_2FA_IN_PROGRESS';

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes, Vote|null $vote = null): int
    {
        if (!($token instanceof TwoFactorTokenInterface)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (self::IS_AUTHENTICATED_2FA_IN_PROGRESS === $attribute) {
                return VoterInterface::ACCESS_GRANTED;
            }

            if (AuthenticatedVoter::PUBLIC_ACCESS === $attribute) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return self::IS_AUTHENTICATED_2FA_IN_PROGRESS === $attribute || AuthenticatedVoter::PUBLIC_ACCESS === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }
}
