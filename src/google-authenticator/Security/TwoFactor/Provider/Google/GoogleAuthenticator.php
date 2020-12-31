<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google;

use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

/**
 * @final
 */
class GoogleAuthenticator implements GoogleAuthenticatorInterface
{
    /**
     * @var GoogleTotpFactory
     */
    private $totpFactory;

    /**
     * @var int
     */
    private $window;

    public function __construct(GoogleTotpFactory $totpFactory, int $window)
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
