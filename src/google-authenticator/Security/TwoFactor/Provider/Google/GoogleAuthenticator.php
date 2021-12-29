<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use function random_bytes;
use function str_replace;

/**
 * @final
 */
class GoogleAuthenticator implements GoogleAuthenticatorInterface
{
    public function __construct(
        private GoogleTotpFactory $totpFactory,
        private int $window,
    ) {
    }

    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        // Strip any user added spaces
        $code = str_replace(' ', '', $code);

        return $this->totpFactory->createTotpForUser($user)->verify($code, null, $this->window);
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
