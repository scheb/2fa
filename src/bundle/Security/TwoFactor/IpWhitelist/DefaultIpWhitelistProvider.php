<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

/**
 * @final
 */
class DefaultIpWhitelistProvider implements IpWhitelistProviderInterface
{
    /**
     * @var string[]
     */
    private $ipWhitelist;

    public function __construct(array $ipWhitelist)
    {
        $this->ipWhitelist = $ipWhitelist;
    }

    public function getWhitelistedIps(AuthenticationContextInterface $context): array
    {
        return $this->ipWhitelist;
    }
}
