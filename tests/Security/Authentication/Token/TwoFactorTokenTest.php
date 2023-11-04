<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Token;

use InvalidArgumentException;
use LogicException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use function serialize;
use function unserialize;

class TwoFactorTokenTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';

    private TwoFactorToken $twoFactorToken;

    protected function setUp(): void
    {
        $twoFactorProviders = [
            'provider1',
            'provider2',
        ];

        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        $this->twoFactorToken = new TwoFactorToken(
            $authenticatedToken,
            null,
            self::FIREWALL_NAME,
            $twoFactorProviders,
        );
    }

    /**
     * @test
     */
    public function createWithCredentials_tokenAndCredentialsGiven_recreateIdenticalTokenWithCredentials(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->twoFactorToken->setAttribute('attributeName', 'attributeValue');

        $credentialsToken = $this->twoFactorToken->createWithCredentials('credentials');
        $this->assertNotSame($this->twoFactorToken, $credentialsToken);
        $this->assertEquals('credentials', $credentialsToken->getCredentials());

        $credentialsToken->eraseCredentials();
        $this->assertEquals($this->twoFactorToken, $credentialsToken);
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
    public function isTwoFactorProviderPrepared_isPrepared_returnTrue(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->assertTrue($this->twoFactorToken->isTwoFactorProviderPrepared('provider1'));
    }

    /**
     * @test
     */
    public function isTwoFactorProviderPrepared_onePreparedProvider_returnTrueOnlyForThatProvider(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->assertTrue($this->twoFactorToken->isTwoFactorProviderPrepared('provider1'));
        $this->assertFalse($this->twoFactorToken->isTwoFactorProviderPrepared('provider2'));
    }

    /**
     * @test
     */
    public function setTwoFactorProviderComplete_wasNotPrepared_throwsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('was not prepared');

        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');
    }

    /**
     * @test
     */
    public function setTwoFactorProviderComplete_completeProvider_continueWithNextProvider(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');
        $this->assertEquals('provider2', $this->twoFactorToken->getCurrentTwoFactorProvider());
    }

    /**
     * @test
     */
    public function setTwoFactorProviderComplete_unknownProvider_throwUnknownTwoFactorProviderException(): void
    {
        $this->expectException(UnknownTwoFactorProviderException::class);

        $this->twoFactorToken->setTwoFactorProviderPrepared('unknownProvider');
        $this->twoFactorToken->setTwoFactorProviderComplete('unknownProvider');
    }

    /**
     * @test
     */
    public function allTwoFactorProvidersAuthenticated_notComplete_returnFalse(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');

        $this->assertFalse($this->twoFactorToken->allTwoFactorProvidersAuthenticated());
    }

    /**
     * @test
     */
    public function allTwoFactorProvidersAuthenticated_allComplete_returnTrue(): void
    {
        $this->twoFactorToken->setTwoFactorProviderPrepared('provider1');
        $this->twoFactorToken->setTwoFactorProviderComplete('provider1');

        $this->twoFactorToken->setTwoFactorProviderPrepared('provider2');
        $this->twoFactorToken->setTwoFactorProviderComplete('provider2');

        $this->assertTrue($this->twoFactorToken->allTwoFactorProvidersAuthenticated());
    }

    /**
     * @test
     */
    public function getAttributes_attributesSet_returnAllAttributes(): void
    {
        $this->twoFactorToken->setAttribute('name', 'value');
        $this->twoFactorToken->setAttribute('otherName', 'otherValue');
        $this->assertEquals([
            'name' => 'value',
            'otherName' => 'otherValue',
        ], $this->twoFactorToken->getAttributes());
    }

    /**
     * @test
     */
    public function hasAttribute_attributeIsSet_returnTrue(): void
    {
        $this->twoFactorToken->setAttribute('name', 'value');
        $this->assertTrue($this->twoFactorToken->hasAttribute('name'));
    }

    /**
     * @test
     */
    public function hasAttribute_attributeNotSet_returnFalse(): void
    {
        $this->twoFactorToken->setAttribute('name', 'value');
        $this->assertFalse($this->twoFactorToken->hasAttribute('otherName'));
    }

    /**
     * @test
     */
    public function getAttribute_attributeIsSet_returnValue(): void
    {
        $this->twoFactorToken->setAttribute('name', 'value');
        $this->assertEquals('value', $this->twoFactorToken->getAttribute('name'));
    }

    /**
     * @test
     */
    public function getAttribute_attributeNotSet_throwInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->twoFactorToken->getAttribute('otherName');
    }

    /**
     * @test
     */
    public function serialize_tokenGiven_unserializeIdenticalToken(): void
    {
        $innerToken = new UsernamePasswordToken($this->createMock(UserInterface::class), self::FIREWALL_NAME, ['ROLE']);
        $twoFactorToken = new TwoFactorToken($innerToken, 'twoFactorCode', self::FIREWALL_NAME, ['2faProvider']);
        $twoFactorToken->setTwoFactorProviderPrepared('2faProvider');
        $twoFactorToken->setAttribute('attributeName', 'attributeValue');

        $unserializedToken = unserialize(serialize($twoFactorToken));

        $this->assertEquals($twoFactorToken, $unserializedToken);
    }
}
