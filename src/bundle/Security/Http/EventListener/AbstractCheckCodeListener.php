<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @internal
 */
abstract class AbstractCheckCodeListener implements EventSubscriberInterface
{
    /**
     * @var PreparationRecorderInterface
     */
    private $preparationRecorder;

    public function __construct(PreparationRecorderInterface $preparationRecorder)
    {
        $this->preparationRecorder = $preparationRecorder;
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!($passport instanceof TwoFactorPassport && $passport->hasBadge(TwoFactorCodeCredentials::class))) {
            return;
        }

        /** @var TwoFactorCodeCredentials $credentialsBadge */
        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        if ($credentialsBadge->isResolved()) {
            return;
        }

        $token = $passport->getTwoFactorToken();
        $providerName = $token->getCurrentTwoFactorProvider();
        if (!$providerName) {
            throw new AuthenticationException('There is no active two-factor provider.');
        }

        if (!$this->preparationRecorder->isTwoFactorProviderPrepared($token->getProviderKey(true), $providerName)) {
            throw new AuthenticationException(sprintf('The two-factor provider "%s" has not been prepared.', $providerName));
        }

        $user = $token->getUser();
        if (null === $user) {
            throw new \RuntimeException('Security token must provide a user.');
        }

        if ($this->isValidCode($providerName, $user, $credentialsBadge->getCode())) {
            $token->setTwoFactorProviderComplete($providerName);
            $credentialsBadge->markResolved();
        }
    }

    /**
     * @param object|string $user
     */
    abstract protected function isValidCode(string $providerName, $user, string $code): bool;
}
