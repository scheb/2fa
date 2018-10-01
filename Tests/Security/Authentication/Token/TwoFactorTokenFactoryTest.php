<?php

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Token;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorTokenFactoryTest extends TestCase
{
    /**
     * @var MockObject|TokenInterface
     */
    private $token;

    /**
     * @var TwoFactorTokenFactory
     */
    private $twoFactorTokenFactory;

    protected function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);

        $this->twoFactorTokenFactory = new TwoFactorTokenFactory(TwoFactorToken::class);
    }

    /**
     * @test
     */
    public function create_onCreate_returnTwoFactorToken()
    {
        $this->assertInstanceOf(
            TwoFactorToken::class,
            $this->twoFactorTokenFactory->create($this->token, null, 'firewallName', ['test1', 'test2'])
        );
    }
}
