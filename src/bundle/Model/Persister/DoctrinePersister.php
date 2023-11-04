<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Persister;

use Doctrine\Persistence\ObjectManager;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * @final
 */
class DoctrinePersister implements PersisterInterface
{
    public function __construct(private readonly ObjectManager $om)
    {
    }

    public function persist(object $user): void
    {
        $this->om->persist($user);
        $this->om->flush();
    }
}
