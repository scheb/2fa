<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\GoogleAuthenticatorCodeEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function random_bytes;
use function str_replace;
use function strlen;

/**
 * @final
 */
class GoogleAuthenticator implements GoogleAuthenticatorInterface
{
    public function __construct(
        private readonly GoogleTotpFactory $totpFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        /** @var 0|positive-int */
        private readonly int $leeway,
    ) {
    }

    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        $event = new TwoFactorCodeEvent($user, $code);
        $this->eventDispatcher->dispatch($event, GoogleAuthenticatorCodeEvents::CHECK);

        // Strip any user added spaces
        $code = str_replace(' ', '', $code);
        if (0 === strlen($code)) {
            return false;
        }

        /** @var non-empty-string $code */
        $isValid = $this->totpFactory->createTotpForUser($user)->verify($code, null, $this->leeway);
        $this->eventDispatcher->dispatch($event, $isValid ? GoogleAuthenticatorCodeEvents::VALID : GoogleAuthenticatorCodeEvents::INVALID);

        return $isValid;
    }

    public function getQRContent(TwoFactorInterface $user): string
    {
        return $this->totpFactory->createTotpForUser($user)->getProvisioningUri();
    }

    public function generateSecret(): string
    {
        return Base32::encodeUpperUnpadded(random_bytes(32));
    }
}
