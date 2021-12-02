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
    /**
     * @var ObjectManager
     */
    private $om;

    public function __construct(ObjectManager $om)
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
