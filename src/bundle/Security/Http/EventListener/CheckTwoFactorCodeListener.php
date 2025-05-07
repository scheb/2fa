<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeCheckEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @final
 */
class CheckTwoFactorCodeListener extends AbstractCheckCodeListener
{
    public const LISTENER_PRIORITY = 0;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        private readonly TwoFactorProviderRegistry $providerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($preparationRecorder);
    }

    protected function isValidCode(string $providerName, object $user, string $code): bool
    {
        $this->eventDispatcher->dispatch(new TwoFactorCodeCheckEvent($user, $code));

        try {
            $authenticationProvider = $this->providerRegistry->getProvider($providerName);
        } catch (InvalidArgumentException) {
            $exception = new TwoFactorProviderNotFoundException('Two-factor provider "'.$providerName.'" not found.');
            $exception->setProvider($providerName);

            throw $exception;
        }

        if (!$authenticationProvider->validateAuthenticationCode($user, $code)) {
            throw new InvalidTwoFactorCodeException(InvalidTwoFactorCodeException::MESSAGE);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', self::LISTENER_PRIORITY]];
    }
}
