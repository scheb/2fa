<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactory;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderDeciderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInitiator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistryInterface;
use Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Condition\AbstractAuthenticationContextTestCase;

class TwoFactorProviderInitiatorTest extends AbstractAuthenticationContextTestCase
{
    private MockObject&TwoFactorTokenFactoryInterface $twoFactorTokenFactory;
    private MockObject&TwoFactorProviderWithNeedsPreparationInterface $statefulProvider;
    private MockObject&TwoFactorProviderWithNeedsPreparationInterface $statelessProvider;
    private MockObject&TwoFactorProviderInterface $withoutNeedsPreparationProvider;
    private MockObject&TwoFactorProviderDeciderInterface $providerDecider;
    private TwoFactorProviderInitiator $initiator;

    protected function setUp(): void
    {
        $this->statefulProvider = $this->createMock(TwoFactorProviderWithNeedsPreparationInterface::class);
        $this->statefulProvider
            ->expects($this->any())
            ->method('needsPreparation')
            ->willReturn(true);

        $this->statelessProvider = $this->createMock(TwoFactorProviderWithNeedsPreparationInterface::class);
        $this->statelessProvider
            ->expects($this->any())
            ->method('needsPreparation')
            ->willReturn(false);

        $this->withoutNeedsPreparationProvider = $this->createMock(TwoFactorProviderInterface::class);

        $providerRegistry = $this->createMock(TwoFactorProviderRegistryInterface::class);
        $providerRegistry
            ->expects($this->any())
            ->method('getAllProviders')
            ->willReturn([
                'statefulProvider' => $this->statefulProvider,
                'statelessProvider' => $this->statelessProvider,
                'withoutNeedsPreparation' => $this->withoutNeedsPreparationProvider,
            ]);
        $providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(function (string $name) {
                return match ($name) {
                    'statefulProvider' => $this->statefulProvider,
                    'statelessProvider' => $this->statelessProvider,
                    'withoutNeedsPreparation' => $this->withoutNeedsPreparationProvider,
                    default => null,
                };
            });

        $this->twoFactorTokenFactory = $this->createMock(TwoFactorTokenFactory::class);

        $this->providerDecider = $this->createMock(TwoFactorProviderDeciderInterface::class);

        $this->initiator = new TwoFactorProviderInitiator($providerRegistry, $this->twoFactorTokenFactory, $this->providerDecider);
    }

    private function createTwoFactorToken(): MockObject&TwoFactorTokenInterface
    {
        return $this->createMock(TwoFactorTokenInterface::class);
    }

    private function createUserWithPreferredProvider(string $preferredProvider): MockObject&UserWithPreferredProviderInterface
    {
        $user = $this->createMock(UserWithPreferredProviderInterface::class);
        $user
            ->expects($this->any())
            ->method('getPreferredTwoFactorProvider')
            ->willReturn($preferredProvider);

        return $user;
    }

    private function stubProvidersReturn(bool $statefulProviderReturns, bool $statelessProviderReturns, bool $legacyProviderReturns): void
    {
        $this->statefulProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->willReturn($statefulProviderReturns);

        $this->statelessProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->willReturn($statelessProviderReturns);

        $this->withoutNeedsPreparationProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->willReturn($legacyProviderReturns);
    }

    private function stubTwoFactorTokenFactoryReturns(MockObject $token): void
    {
        $this->twoFactorTokenFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($token);
    }

    private function stubTwoFactorProviderDeciderReturns(string|null $preferredProvider): void
    {
        $this->providerDecider
            ->expects($this->once())
            ->method('getPreferredTwoFactorProvider')
            ->willReturn($preferredProvider);
    }

    #[Test]
    public function beginAuthentication_multipleProviders_beginAuthenticationOnEachTwoFactorProvider(): void
    {
        $context = $this->createAuthenticationContext();

        $this->statefulProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->with($context);

        $this->statelessProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->with($context);

        $this->withoutNeedsPreparationProvider
            ->expects($this->once())
            ->method('beginAuthentication')
            ->with($context);

        $this->initiator->beginTwoFactorAuthentication($context);
    }

    #[Test]
    public function beginAuthentication_oneProviderStarts_returnTwoFactorToken(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubProvidersReturn(false, true, false);

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($originalToken, self::FIREWALL_NAME, ['statelessProvider'])
            ->willReturn($twoFactorToken);

        $returnValue = $this->initiator->beginTwoFactorAuthentication($context);
        $this->assertSame($twoFactorToken, $returnValue);
    }

    #[Test]
    public function beginAuthentication_noProviderStarts_returnNull(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubProvidersReturn(false, false, false);

        $returnValue = $this->initiator->beginTwoFactorAuthentication($context);
        $this->assertNull($returnValue);
    }

    #[Test]
    public function beginAuthentication_hasPreferredProvider_setThatProviderPreferred(): void
    {
        $user = $this->createUserWithPreferredProvider('preferredProvider');
        $originalToken = $this->createToken();
        $twoFactorToken = $this->createTwoFactorToken();

        $context = $this->createAuthenticationContext(null, $originalToken, $user);
        $this->stubProvidersReturn(true, true, true);
        $this->stubTwoFactorTokenFactoryReturns($twoFactorToken);
        $this->stubTwoFactorProviderDeciderReturns('preferredProvider');

        $twoFactorToken
            ->expects($this->once())
            ->method('preferTwoFactorProvider')
            ->with('preferredProvider');

        $this->initiator->beginTwoFactorAuthentication($context);
    }

    #[Test]
    public function beginAuthentication_statelessProviderPrepared_setThatProviderIsPrepared(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubProvidersReturn(true, true, true);

        $twoFactorToken = $this->createTwoFactorToken();
        $this->stubTwoFactorTokenFactoryReturns($twoFactorToken);
        $twoFactorToken
            ->expects($this->once())
            ->method('setTwoFactorProviderPrepared')
            ->with('statelessProvider');

        $this->initiator->beginTwoFactorAuthentication($context);
    }
}
