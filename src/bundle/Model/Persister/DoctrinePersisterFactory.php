<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Model\Persister;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * @internal For the DIC to construct a DoctrinePersister instance
 *
 * @final
 */
class DoctrinePersisterFactory
{
    private readonly ManagerRegistry $managerRegistry;

    public function __construct(
        ManagerRegistry|null $managerRegistry,
        private readonly string|null $objectManagerName,
    ) {
        if (null === $managerRegistry) {
            $msg = 'scheb/2fa-bundle requires Doctrine to manage the user entity. If you don\'t want something else ';
            $msg .= 'for persistence, implement your own persister service and configure it in scheb_two_factor.persister.';

            throw new InvalidArgumentException($msg);
        }

        $this->managerRegistry = $managerRegistry;
    }

    public function getPersister(): PersisterInterface
    {
        $objectManager = $this->managerRegistry->getManager($this->objectManagerName);

        return new DoctrinePersister($objectManager);
    }
}
