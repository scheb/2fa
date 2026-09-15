<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\AuthenticationTrustResolver;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationTrustResolverTest extends TestCase
{
    private MockObject&AuthenticationTrustResolverInterface $decoratedTrustResolver;
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

    #[Test]
    #[DataProvider('provideReturnedResult')]
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
     * @return array<array<string>>
     */
    public static function provideRecencyMethods(): array
    {
        return [
            ['isAuthenticatedRecently'],
            ['isAuthenticatedVeryRecently'],
        ];
    }

    #[Test]
    #[DataProvider('provideRecencyMethods')]
    public function isAuthenticatedRecently_twoFactorToken_returnFalse(string $method): void
    {
        $decoratedTrustResolver = new RecencyAwareTrustResolver();
        $decoratedTrustResolver->result = true;
        $trustResolver = new AuthenticationTrustResolver($decoratedTrustResolver);

        $returnValue = $trustResolver->$method($this->createMock(TwoFactorTokenInterface::class));
        $this->assertFalse($returnValue);
        $this->assertNull($decoratedTrustResolver->calledMethod);
    }

    #[Test]
    #[DataProvider('provideRecencyMethods')]
    public function isAuthenticatedRecently_decoratedTrustResolverWithoutTheMethod_returnFalse(string $method): void
    {
        // The method exists on the interface since Symfony 8.2 only
        $this->decoratedTrustResolver
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->trustResolver->$method($this->createMock(TokenInterface::class));
        $this->assertFalse($returnValue);
    }

    #[Test]
    #[DataProvider('provideRecencyMethods')]
    public function isAuthenticatedRecently_notTwoFactorToken_returnResultFromDecoratedTrustResolver(string $method): void
    {
        $decoratedTrustResolver = new RecencyAwareTrustResolver();
        $decoratedTrustResolver->result = true;
        $trustResolver = new AuthenticationTrustResolver($decoratedTrustResolver);

        $returnValue = $trustResolver->$method($this->createMock(TokenInterface::class));
        $this->assertTrue($returnValue);
        $this->assertEquals($method, $decoratedTrustResolver->calledMethod);
    }

    #[Test]
    public function isFullFledged_twoFactorToken_returnFalse(): void
    {
        $this->decoratedTrustResolver
            ->expects($this->never())
            ->method($this->anything());

        $returnValue = $this->trustResolver->isFullFledged($this->createMock(TwoFactorTokenInterface::class));
        $this->assertFalse($returnValue);
    }

    #[Test]
    #[DataProvider('provideReturnedResult')]
    public function isFullFledged_notTwoFactorToken_returnResultFromDecoratedTrustResolver(bool $returnedResult): void
    {
        $this->decoratedTrustResolver
            ->expects($this->once())
            ->method('isFullFledged')
            ->willReturn($returnedResult);

        $returnValue = $this->trustResolver->isFullFledged($this->createMock(TokenInterface::class));
        $this->assertEquals($returnedResult, $returnValue);
    }

    #[Test]
    #[DataProvider('provideReturnedResult')]
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
