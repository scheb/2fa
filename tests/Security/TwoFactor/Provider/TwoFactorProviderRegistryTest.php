<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorProviderRegistryTest extends TestCase
{
    private MockObject|TwoFactorProviderInterface $twoFactorProvider2;
    private MockObject|TwoFactorProviderInterface $twoFactorProvider1;
    private TwoFactorProviderRegistry $providerRegistry;

    protected function setUp(): void
    {
        $this->twoFactorProvider1 = $this->createMock(TwoFactorProviderInterface::class);
        $this->twoFactorProvider2 = $this->createMock(TwoFactorProviderInterface::class);

        $this->providerRegistry = new TwoFactorProviderRegistry([
            'provider1' => $this->twoFactorProvider1,
            'provider2' => $this->twoFactorProvider2,
        ]);
    }

    /**
     * @test
     */
    public function getProvider_exists_returnTwoFactorProvider(): void
    {
        $returnValue = $this->providerRegistry->getProvider('provider2');
        $this->assertSame($this->twoFactorProvider2, $returnValue);
    }

    /**
     * @test
     */
    public function getProvider_notExists_throwUnknownTwoFactorProviderException(): void
    {
        $this->expectException(UnknownTwoFactorProviderException::class);
        $this->providerRegistry->getProvider('unknownProvider');
    }

    /**
     * @test
     */
    public function getAllProviders_hasRegisteredProviders_returnAllTwoFactorProviders(): void
    {
        $returnValue = $this->providerRegistry->getAllProviders();

        $this->assertCount(2, $returnValue);
        $this->assertContains($this->twoFactorProvider1, $returnValue);
        $this->assertContains($this->twoFactorProvider2, $returnValue);
    }
}
