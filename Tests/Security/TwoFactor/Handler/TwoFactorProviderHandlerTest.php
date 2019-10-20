<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\PreferredProviderInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactory;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\TwoFactorProviderHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;

class TwoFactorProviderHandlerTest extends AbstractAuthenticationHandlerTestCase
{
    /**
     * @var MockObject|TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var MockObject|TwoFactorTokenFactoryInterface
     */
    private $twoFactorTokenFactory;

    /**
     * @var MockObject|TwoFactorTokenInterface
     */
    private $twoFactorToken;
    /**
     * @var MockObject|TwoFactorProviderInterface
     */
    private $provider1;

    /**
     * @var MockObject|TwoFactorProviderInterface
     */
    private $provider2;

    /**
     * @var TwoFactorProviderHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(TwoFactorProviderInterface::class);
        $this->provider2 = $this->createMock(TwoFactorProviderInterface::class);

        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->providerRegistry
            ->expects($this->any())
            ->method('getAllProviders')
            ->willReturn([
                'test1' => $this->provider1,
                'test2' => $this->provider2,
            ]);

        $this->twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorTokenFactory = $this->createMock(TwoFactorTokenFactory::class);

        $this->handler = new TwoFactorProviderHandler($this->providerRegistry, $this->twoFactorTokenFactory);
    }

    private function createTwoFactorToken(): MockObject
    {
        return $this->createMock(TwoFactorTokenInterface::class);
    }

    private function createUserWithPreferredProvider(string $preferredProvider): MockObject
    {
        $user = $this->createMock(PreferredProviderInterface::class);
        $user
            ->expects($this->any())
            ->method('getPreferredTwoFactorProvider')
            ->willReturn($preferredProvider);

        return $user;
    }

    private function stubProvidersReturn(bool $provider1Returns, bool $provider2Returns): void
    {
        $this->provider1
            ->expects($this->any())
            ->method('beginAuthentication')
            ->willReturn($provider1Returns);

        $this->provider2
            ->expects($this->any())
            ->method('beginAuthentication')
            ->willReturn($provider2Returns);
    }

    private function stubTwoFactorTokenFactoryReturns(MockObject $token): void
    {
        $this->twoFactorTokenFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($token);
    }

    /**
     * @test
     */
    public function beginAuthentication_multipleProviders_beginAuthenticationOnEachTwoFactorProvider(): void
    {
        $context = $this->createAuthenticationContext();

        $this->provider1
            ->expects($this->once())
            ->method('beginAuthentication')
            ->with($context);

        $this->provider2
            ->expects($this->once())
            ->method('beginAuthentication')
            ->with($context);

        $this->handler->beginTwoFactorAuthentication($context);
    }

    /**
     * @test
     */
    public function beginAuthentication_oneProviderStarts_returnTwoFactorToken(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubProvidersReturn(false, true);

        $twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->twoFactorTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($originalToken, null, self::FIREWALL_NAME, ['test2'])
            ->willReturn($twoFactorToken);

        /** @var TwoFactorTokenInterface $returnValue */
        $returnValue = $this->handler->beginTwoFactorAuthentication($context);
        $this->assertSame($twoFactorToken, $returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_noProviderStarts_returnOriginalToken(): void
    {
        $originalToken = $this->createToken();
        $context = $this->createAuthenticationContext(null, $originalToken);
        $this->stubProvidersReturn(false, false);

        $returnValue = $this->handler->beginTwoFactorAuthentication($context);
        $this->assertSame($originalToken, $returnValue);
    }

    /**
     * @test
     */
    public function beginAuthentication_hasPreferredProvider_setThatProviderPreferred(): void
    {
        $user = $this->createUserWithPreferredProvider('preferredProvider');
        $originalToken = $this->createToken();
        $twoFactorToken = $this->createTwoFactorToken();

        $context = $this->createAuthenticationContext(null, $originalToken, $user);
        $this->stubProvidersReturn(true, true);
        $this->stubTwoFactorTokenFactoryReturns($twoFactorToken);

        $twoFactorToken
            ->expects($this->once())
            ->method('preferTwoFactorProvider')
            ->with('preferredProvider');

        $this->handler->beginTwoFactorAuthentication($context);
    }
}
