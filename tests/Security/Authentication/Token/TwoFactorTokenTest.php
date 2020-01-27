<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Token;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorTokenTest extends TestCase
{
    /**
     * @var TwoFactorToken
     */
    private $twoFactorToken;

    protected function setUp(): void
    {
        $twoFactorProviders = [
            'provider1',
            'provider2',
        ];
        $this->twoFactorToken = new TwoFactorToken($this->createMock(TokenInterface::class), null, 'firewallName', $twoFactorProviders);
    }

    /**
     * @test
     */
    public function preferTwoFactorProvider_preferOtherProvider_becomesCurrentProvider(): void
    {
        $this->twoFactorToken->preferTwoFactorProvider('provider2');
        $this->assertEquals('provider2', $this->twoFactorToken->getCurrentTwoFactorProvider());
    }

    /**
     * @test
     */
    public function preferTwoFactorProvider_preferOtherProvider_returnsPreferredProviderFirst(): void
    {
        $this->twoFactorToken->preferTwoFactorProvider('provider2');
        $this->assertEquals(['provider2', 'provider1'], $this->twoFactorToken->getTwoFactorProviders());
    }

    /**
     * @test
     */
    public function preferTwoFactorProvider_unknownProvider_throwUnknownTwoFactorProviderException(): void
    {
        $this->expectException(UnknownTwoFactorProviderException::class);
        $this->twoFactorToken->preferTwoFactorProvider('unknownProvider');
    }

    /**
     * @test
     */
    public function getCurrentTwoFactorProvider_defaultOrderGiven_returnFirstProvider(): void
    {
        $this->assertEquals('provider1', $this->twoFactorToken->getCurrentTwoFactorProvider());
    }

    /**
     * @test
     */
    public function setTwoFactorProviderComplete_completeProvider_continueWithNextProvider(): void
    {
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');
        $this->assertEquals('provider2', $this->twoFactorToken->getCurrentTwoFactorProvider());
    }

    /**
     * @test
     */
    public function setTwoFactorProviderComplete_unknownProvider_throwUnknownTwoFactorProviderException(): void
    {
        $this->expectException(UnknownTwoFactorProviderException::class);
        $this->twoFactorToken->setTwoFactorProviderComplete('unknownProvider');
    }

    /**
     * @test
     */
    public function allTwoFactorProvidersAuthenticated_notComplete_returnFalse(): void
    {
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');
        $this->assertFalse($this->twoFactorToken->allTwoFactorProvidersAuthenticated());
    }

    /**
     * @test
     */
    public function allTwoFactorProvidersAuthenticated_allComplete_returnTrue(): void
    {
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');
        $this->twoFactorToken->setTwoFactorProviderComplete('provider2');
        $this->assertTrue($this->twoFactorToken->allTwoFactorProvidersAuthenticated());
    }

    /**
     * @test
     */
    public function serialize_tokenGiven_unserializeIdenticalToken(): void
    {
        $innerToken = new UsernamePasswordToken('username', 'credentials', 'firewallName', ['ROLE']);
        $twoFactorToken = new TwoFactorToken($innerToken, 'twoFactorCode', 'firewallName', ['2faProvider']);

        $unserializedToken = unserialize(serialize($twoFactorToken));

        $this->assertEquals($twoFactorToken, $unserializedToken);
    }
}
