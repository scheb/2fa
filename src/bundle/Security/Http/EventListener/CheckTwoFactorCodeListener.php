<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * @final
 */
class CheckTwoFactorCodeListener extends AbstractCheckCodeListener
{
    public const LISTENER_PRIORITY = 0;

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    public function __construct(
        PreparationRecorderInterface $preparationRecorder,
        TwoFactorProviderRegistry $providerRegistry
    ) {
        parent::__construct($preparationRecorder);
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * @param object|string $user
     */
    protected function isValidCode(string $providerName, $user, string $code): bool
    {
        try {
            $authenticationProvider = $this->providerRegistry->getProvider($providerName);
        } catch (\InvalidArgumentException $e) {
            $exception = new TwoFactorProviderNotFoundException('Two-factor provider "'.$providerName.'" not found.');
            $exception->setProvider($providerName);
            throw $exception;
        }

        if (!$authenticationProvider->validateAuthenticationCode($user, $code)) {
            throw new InvalidTwoFactorCodeException(InvalidTwoFactorCodeException::MESSAGE);
        }

        return true;
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', self::LISTENER_PRIORITY]];
    }
}
