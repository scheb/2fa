<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/**
 * @final
 */
class TotpAuthenticator implements TotpAuthenticatorInterface
{
    /**
     * @var TotpFactory
     */
    private $totpFactory;

    /**
     * @var int
     */
    private $window;

    public function __construct(TotpFactory $totpFactory, int $window)
    {
        $this->totpFactory = $totpFactory;
        $this->window = $window;
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
