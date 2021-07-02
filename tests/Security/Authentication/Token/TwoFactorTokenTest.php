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
    private const FIREWALL_NAME = 'firewallName';

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
        $this->twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, ['getRoleNames' => ['ROLE_USER']]),
            null,
            self::FIREWALL_NAME,
            $twoFactorProviders
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
    public function isTwoFactorProviderPrepared_onePreparedProvider_returnTrueOnlyForThatProvider()
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
        $this->expectException(\LogicException::class);
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
    public function serialize_tokenGiven_unserializeIdenticalToken(): void
    {
        $innerToken = new UsernamePasswordToken('username', 'credentials', self::FIREWALL_NAME, ['ROLE']);
        $twoFactorToken = new TwoFactorToken($innerToken, 'twoFactorCode', self::FIREWALL_NAME, ['2faProvider']);
        $twoFactorToken->setTwoFactorProviderPrepared('2faProvider');

        $unserializedToken = unserialize(serialize($twoFactorToken));

        $this->assertEquals($twoFactorToken, $unserializedToken);
    }

    /**
     * @test
     */
    public function getRoles_getRoleNamesExists_rolesReturned(): void
    {
        if (!method_exists(TokenInterface::class, 'getRoleNames')) {
            self::markTestSkipped('TokenInterface::getRoleNames() is required');
        }

        $twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, ['getRoleNames' => ['ROLE_USER']]),
            null,
            self::FIREWALL_NAME,
            ['2faProvider']
        );
        $roles = $twoFactorToken->getRoles();
        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * @test
     */
    public function getRoleNames_getRoleNamesExists_rolesReturned(): void
    {
        if (!method_exists(TokenInterface::class, 'getRoleNames')) {
            self::markTestSkipped('TokenInterface::getRoleNames() is required');
        }

        $twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, ['getRoleNames' => ['ROLE_USER']]),
            null,
            self::FIREWALL_NAME,
            ['2faProvider']
        );
        $roles = $twoFactorToken->getRoleNames();
        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * @test
     */
    public function getRoles_getRolesExists_rolesReturned(): void
    {
        if (!method_exists(TokenInterface::class, 'getRoles')) {
            self::markTestSkipped('TokenInterface::getRoles() is required');
        }

        $twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, ['getRoles' => ['ROLE_USER']]),
            null,
            self::FIREWALL_NAME,
            ['2faProvider']
        );
        $roles = $twoFactorToken->getRoles();
        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * @test
     */
    public function getRoleNames_getRolesExists_rolesReturned(): void
    {
        if (!method_exists(TokenInterface::class, 'getRoles')) {
            self::markTestSkipped('TokenInterface::getRoles() is required');
        }

        $twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, ['getRoles' => ['ROLE_USER']]),
            null,
            self::FIREWALL_NAME,
            ['2faProvider']
        );
        $roles = $twoFactorToken->getRoleNames();
        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * @test
     */
    public function getRoleNames_getRolesReturnsUnknownRoleObjects_nothingReturned(): void
    {
        if (!method_exists(TokenInterface::class, 'getRoles')) {
            self::markTestSkipped('TokenInterface::getRoles() is required');
        }

        $twoFactorToken = new TwoFactorToken(
            $this->createConfiguredMock(TokenInterface::class, [
                'getRoles' => [
                    new class() {
                    },
                ],
            ]),
            null,
            self::FIREWALL_NAME,
            ['2faProvider']
        );

        $roles = $twoFactorToken->getRoleNames();
        $this->assertIsArray($roles);
        $this->assertEmpty($roles);
    }
}
