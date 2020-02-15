<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model;

interface PreferredProviderInterface
{
    /**
     * Return the alias of the preferred two-factor provider (if chosen by the user).
     */
    public function getPreferredTwoFactorProvider(): ?string;
}
