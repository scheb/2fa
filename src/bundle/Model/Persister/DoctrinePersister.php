<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Persister;

use Doctrine\Common\Persistence\ObjectManager as LegacyObjectManager;
use Doctrine\Persistence\ObjectManager;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * @final
 */
class DoctrinePersister implements PersisterInterface
{
    /**
     * @var ObjectManager|LegacyObjectManager
     */
    private $om;

    /**
     * Initialize a persister for doctrine entities.
     *
     * @param ObjectManager|LegacyObjectManager $om
     */
    public function __construct($om)
    {
        $this->om = $om;
    }

    /**
     * Persist the user entity.
     *
     * @param object $user
     */
    public function persist($user): void
    {
        $this->om->persist($user);
        $this->om->flush();
    }
}
