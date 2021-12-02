<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model;

interface PersisterInterface
{
    /**
     * Persist the user entity.
     */
    public function persist(object $user): void;
}
