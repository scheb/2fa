<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use function random_bytes;
use function str_replace;
use function strlen;

/**
 * @final
 */
class TotpAuthenticator implements TotpAuthenticatorInterface
{
    public function __construct(
        private readonly TotpFactory $totpFactory,
        /** @var 0|positive-int */
        private readonly int $leeway,
    ) {
    }

    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        // Strip any user added spaces
        $code = str_replace(' ', '', $code);
        if (0 === strlen($code)) {
            return false;
        }

        /** @var non-empty-string $code */
        return $this->totpFactory->createTotpForUser($user)->verify($code, null, $this->leeway);
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
