<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @internal
 */
abstract class AbstractCheckCodeListener implements EventSubscriberInterface
{
    public function __construct(private PreparationRecorderInterface $preparationRecorder)
    {
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(TwoFactorCodeCredentials::class)) {
            return;
        }

        /** @var TwoFactorCodeCredentials $credentialsBadge */
        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        if ($credentialsBadge->isResolved()) {
            return;
        }

        /** @var TwoFactorCodeCredentials $credentialsBadge */
        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        $token = $credentialsBadge->getTwoFactorToken();
        $providerName = $token->getCurrentTwoFactorProvider();
        if (!$providerName) {
            throw new AuthenticationException('There is no active two-factor provider.');
        }

        if (!$this->preparationRecorder->isTwoFactorProviderPrepared($token->getFirewallName(), $providerName)) {
            throw new AuthenticationException(sprintf('The two-factor provider "%s" has not been prepared.', $providerName));
        }

        if ($this->isValidCode($providerName, $token->getUser(), $credentialsBadge->getCode())) {
            $token->setTwoFactorProviderComplete($providerName);
            $credentialsBadge->markResolved();
        }
    }

    abstract protected function isValidCode(string $providerName, mixed $user, string $code): bool;
}
