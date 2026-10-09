<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use InvalidArgumentException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\AuthenticationMethodProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class CheckTwoFactorCodeListener extends AbstractCheckCodeListener
{
    public const int LISTENER_PRIORITY = 0;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        private readonly TwoFactorProviderRegistry $providerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($preparationRecorder);
    }

    protected function isValidCode(Passport $passport, string $providerName, object $user, string $code): bool
    {
        $this->eventDispatcher->dispatch(
            new TwoFactorCodeEvent($user, $code),
            TwoFactorAuthenticationEvents::CHECK,
        );

        try {
            $authenticationProvider = $this->providerRegistry->getProvider($providerName);

            // Symfony 8.2 records on the token which authentication methods were proven, from that badge
            $authenticationMethod = $authenticationProvider instanceof AuthenticationMethodProviderInterface ? $authenticationProvider->getAuthenticationMethod() : null;
            /** @psalm-suppress UndefinedClass */
            if (null !== $authenticationMethod && class_exists(AuthenticationMethodBadge::class)) {
                /** @psalm-suppress UndefinedClass, InvalidArgument, MixedMethodCall, MixedArgument */
                $passport->addBadge(new AuthenticationMethodBadge($authenticationMethod));
            }
        } catch (InvalidArgumentException) {
            $exception = new TwoFactorProviderNotFoundException('Two-factor provider "'.$providerName.'" not found.');
            $exception->setProvider($providerName);

            throw $exception;
        }

        if ($authenticationProvider->validateAuthenticationCode($user, $code)) {
            $this->eventDispatcher->dispatch(
                new TwoFactorCodeEvent($user, $code),
                TwoFactorAuthenticationEvents::CODE_VALID,
            );

            return true;
        }

        $this->eventDispatcher->dispatch(
            new TwoFactorCodeEvent($user, $code),
            TwoFactorAuthenticationEvents::CODE_INVALID,
        );

        throw new InvalidTwoFactorCodeException(InvalidTwoFactorCodeException::MESSAGE);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', self::LISTENER_PRIORITY]];
    }
}
