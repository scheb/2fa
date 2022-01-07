<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Scheb\TwoFactorBundle\Security\Authentication\AuthenticationTrustResolver;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationTrustResolverTest extends TestCase
{
    private MockObject|AuthenticationTrustResolverInterface $decoratedTrustResolver;
    private AuthenticationTrustResolver $trustResolver;

    /**
     * @param string[] $decoratedExtraMethods
     */
    private function initTrustResolver(array $decoratedExtraMethods = []): void
    {
        $this->decoratedTrustResolver = $this->createMockWithExtraMethod(AuthenticationTrustResolverInterface::class, $decoratedExtraMethods);
        $this->trustResolver = new AuthenticationTrustResolver($this->decoratedTrustResolver);
    }

    /**
     * @param string[] $addMethods
     */
    private function createMockWithExtraMethod(string $className, array $addMethods): MockObject
    {
        return $this->getMockBuilder($className)->addMethods($addMethods)->getMockForAbstractClass();
    }

    /**
     * @return array<array<bool>>
     */
    public function provideReturnedResult(): array
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
    public function isAnonymous_tokenGiven_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->requireAtMostSymfony5_4();
        $this->initTrustResolver();

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAnonymous')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAnonymous($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isRememberMe_tokenGiven_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->initTrustResolver();
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
        $this->initTrustResolver();
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
        $this->initTrustResolver();
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
    public function isAnonymous_sf54DecoratedMethodDeclared_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->requireAtMostSymfony5_4();
        $this->initTrustResolver([]); // In Symfony 6.0 the "isAnonymous" is declared

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAnonymous')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAnonymous($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     */
    public function isAnonymous_sf6DecoratedMethodMissing_throwRuntimeException(): void
    {
        $this->requireAtLeastSymfony6_0();
        $this->initTrustResolver([]); // In Symfony 6.0 the "isAnonymous" is NOT declared

        $this->expectException(RuntimeException::class);
        $this->trustResolver->isAnonymous($this->createMock(TokenInterface::class));
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isAnonymous_sf6DecoratedMethodDeclared_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->requireAtLeastSymfony6_0();
        $this->initTrustResolver(['isAnonymous']); // Extra method, that doesn't exist in the Symfony 6.0 interface

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAnonymous')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAnonymous($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isAuthenticated_sf54DecoratedMethodMissing_returnNegatedResultFromIsAnonymous(bool $returnedResult): void
    {
        $this->requireAtMostSymfony5_4();
        $this->initTrustResolver([]); // In Symfony 6.0 the "isAnonymous" is declared

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAnonymous')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAuthenticated($this->createMock(TokenInterface::class));
        $this->assertEquals(!$returnedResult, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isAuthenticated_sf54DecoratedMethodDeclared_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->requireAtMostSymfony5_4();
        $this->initTrustResolver(['isAuthenticated']); // Extra method, that doesn't exist in the Symfony 5.4 interface

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn($returnedResult);

        $trustResolver = new AuthenticationTrustResolver($this->decoratedTrustResolver);

        $returnValue = $trustResolver->isAuthenticated($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    /**
     * @test
     * @dataProvider provideReturnedResult
     */
    public function isAuthenticated_sf6DecoratedMethodDeclared_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->requireAtLeastSymfony6_0();
        $this->initTrustResolver([]); // In Symfony 6.0 the "isAuthenticated" is declared

        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isAuthenticated($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }
}
