<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Persister;

use Doctrine\Common\Persistence\ManagerRegistry as LegacyManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * @final
 *
 * @internal For the DIC to construct a DoctrinePersister instance
 */
class DoctrinePersisterFactory
{
    /**
     * @var ManagerRegistry|LegacyManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var string|null
     */
    private $objectManagerName;

    /**
     * @param ManagerRegistry|LegacyManagerRegistry|null $managerRegistry
     */
    public function __construct($managerRegistry, ?string $objectManagerName)
    {
        if (!$managerRegistry) {
            $msg = 'scheb/2fa-bundle requires Doctrine to manage the user entity. If you don\'t want something else ';
            $msg .= 'for persistence, implement your own persister service and configure it in scheb_two_factor.persister.';
            throw new \InvalidArgumentException($msg);
        }

        $this->managerRegistry = $managerRegistry;
        $this->objectManagerName = $objectManagerName;
    }

    public function getPersister(): PersisterInterface
    {
        $objectManager = $this->managerRegistry->getManager($this->objectManagerName);

        return new DoctrinePersister($objectManager);
    }
}
