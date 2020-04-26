<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Model\Persister;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Persister\DoctrinePersister;
use Scheb\TwoFactorBundle\Tests\TestCase;

class DoctrinePersisterTest extends TestCase
{
    /**
     * @var MockObject|ObjectManager
     */
    private $objectManager;

    /**
     * @var DoctrinePersister
     */
    private $persister;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->persister = new DoctrinePersister($this->objectManager);
    }

    /**
     * @test
     */
    public function persist_persistObject_callPersistAndFlush(): void
    {
        $user = new \stdClass(); //Some user object

        //Mock the EntityManager
        $this->objectManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->objectManager
            ->expects($this->once())
            ->method('flush');

        $this->persister->persist($user);
    }
}
