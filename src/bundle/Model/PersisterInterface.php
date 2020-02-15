<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model;

interface PersisterInterface
{
    /**
     * Persist the user entity.
     *
     * @param object $user
     */
    public function persist($user): void;
}
