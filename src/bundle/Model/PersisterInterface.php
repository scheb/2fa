<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model;

/**
 * Saves a user entity after its two-factor data changed.
 */
interface PersisterInterface
{
    /**
     * Persist the user entity.
     */
    public function persist(object $user): void;
}
