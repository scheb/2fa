<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Token;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorTokenFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function create_onCreate_returnTwoFactorToken(): void
    {
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $twoFactorTokenFactory = new TwoFactorTokenFactory();

        $twoFactorToken = $twoFactorTokenFactory->create($authenticatedToken, 'credentials', 'firewallName', ['test1', 'test2']);

        $this->assertInstanceOf(TwoFactorToken::class, $twoFactorToken);
        $this->assertSame($authenticatedToken, $twoFactorToken->getAuthenticatedToken());
        $this->assertEquals('credentials', $twoFactorToken->getCredentials());
        $this->assertEquals('firewallName', $twoFactorToken->getProviderKey());
        $this->assertEquals(['test1', 'test2'], $twoFactorToken->getTwoFactorProviders());
    }
}
