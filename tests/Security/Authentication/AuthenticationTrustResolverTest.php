<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\AuthenticationTrustResolver;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationTrustResolverTest extends TestCase
{
    private MockObject|AuthenticationTrustResolverInterface $decoratedTrustResolver;
    private AuthenticationTrustResolver $trustResolver;

    protected function setUp(): void
    {
        $this->decoratedTrustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);
        $this->trustResolver = new AuthenticationTrustResolver($this->decoratedTrustResolver);
    }

    /**
     * @return array<array<bool>>
     */
    public static function provideReturnedResult(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isRememberMe_tokenGiven_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isRememberMe')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isRememberMe($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     */
    public function isFullFledged_twoFactorToken_returnFalse(): void
    {
        $this->decoratedTrustResolver
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->trustResolver->isFullFledged($this->createMock(TwoFactorTokenInterface::class));
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isFullFledged_notTwoFactorToken_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isFullFledged')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isFullFledged($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isAuthenticated_tokenGiven_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAuthenticated($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }
}
