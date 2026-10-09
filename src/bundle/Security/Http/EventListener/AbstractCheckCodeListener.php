<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\AuthenticationMethodBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use function assert;
use function class_exists;
use function sprintf;

/**
 * @internal
 */
abstract class AbstractCheckCodeListener implements EventSubscriberInterface
{
    public function __construct(private readonly PreparationRecorderInterface $preparationRecorder)
    {
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(TwoFactorCodeCredentials::class)) {
            return;
        }

        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        assert($credentialsBadge instanceof TwoFactorCodeCredentials);
        if ($credentialsBadge->isResolved()) {
            return;
        }

        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);
        assert($credentialsBadge instanceof TwoFactorCodeCredentials);
        $token = $credentialsBadge->getTwoFactorToken();
        $providerName = $token->getCurrentTwoFactorProvider();
        if (null === $providerName || !$providerName) {
            throw new AuthenticationException('There is no active two-factor provider.');
        }

        if (!$this->preparationRecorder->isTwoFactorProviderPrepared($token->getFirewallName(), $providerName)) {
            throw new AuthenticationException(sprintf('The two-factor provider "%s" has not been prepared.', $providerName));
        }

        if (!$this->isValidCode($providerName, $token->getUser(), $credentialsBadge->getCode())) {
            return;
        }

        // Symfony 8.2 records on the token which authentication methods were proven, from that badge
        $authenticationMethod = $this->getAuthenticationMethod($providerName);
        /** @psalm-suppress UndefinedClass */
        if (null !== $authenticationMethod && class_exists(AuthenticationMethodBadge::class)) {
            /** @psalm-suppress UndefinedClass, InvalidArgument, MixedMethodCall, MixedArgument */
            $passport->addBadge(new AuthenticationMethodBadge($authenticationMethod));
        }

        // Badge is resolved, but authentication for provider is not complete yet
        $credentialsBadge->markResolved();
    }

    abstract protected function isValidCode(string $providerName, object $user, string $code): bool;

    /**
     * Returns the "amr" value of RFC 8176 that a valid code proves, if known.
     */
    abstract protected function getAuthenticationMethod(string $providerName): string|null;
}
