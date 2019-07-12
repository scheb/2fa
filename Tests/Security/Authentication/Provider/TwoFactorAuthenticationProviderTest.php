<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Authentication\Provider\TwoFactorAuthenticationProvider;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationRecorder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class TwoFactorAuthenticationProviderTest extends TestCase
{
    const FIREWALL_NAME = 'firewallName';
    /**
     * @var MockObject|TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var MockObject|BackupCodeManagerInterface
     */
    private $backupCodeManager;

    /**
     * @var MockObject|TwoFactorProviderPreparationRecorder
     */
    private $preparationRecorder;

    /**
     * @var MockObject|UserInterface
     */
    private $user;

    /**
     * @var MockObject|TokenInterface
     */
    private $authenticatedToken;

    /**
     * @var MockObject|TwoFactorProviderInterface
     */
    private $twoFactorProvider1;

    /**
     * @var MockObject|TwoFactorProviderInterface
     */
    private $twoFactorProvider2;

    /**
     * @var TwoFactorToken
     */
    private $twoFactorToken;

    /**
     * @var TwoFactorAuthenticationProvider
     */
    private $authenticationProvider;

    protected function setUp(): void
    {
        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->preparationRecorder = $this->createMock(TwoFactorProviderPreparationRecorder::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->authenticatedToken = $this->createMock(TokenInterface::class);
        $this->authenticatedToken
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->twoFactorProvider1 = $this->createMock(TwoFactorProviderInterface::class);
        $this->twoFactorProvider2 = $this->createMock(TwoFactorProviderInterface::class);
    }

    private function createAuthenticationProviderWithMultiFactor(bool $multiFactor): void
    {
        $this->providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(function (string $providerName) {
                switch ($providerName) {
                    case 'provider1':
                        return $this->twoFactorProvider1;
                    case 'provider2':
                        return $this->twoFactorProvider2;
                    default:
                        throw new \InvalidArgumentException();
                }
            });

        $options['multi_factor'] = $multiFactor;
        $this->authenticationProvider = new TwoFactorAuthenticationProvider(
            self::FIREWALL_NAME,
            $options,
            $this->providerRegistry,
            $this->backupCodeManager,
            $this->preparationRecorder
        );
    }

    public function createTwoFactorToken(string $firewallName, ?string $credentials, array $twoFactorProviders = []): TwoFactorToken
    {
        $this->twoFactorToken = new TwoFactorToken($this->authenticatedToken, $credentials, $firewallName, $twoFactorProviders);

        return $this->twoFactorToken;
    }

    public function createSupportedTwoFactorTokenWithProviders(array $twoFactorProviders): TwoFactorToken
    {
        return $this->createTwoFactorToken(self::FIREWALL_NAME, 'credentials', $twoFactorProviders);
    }

    private function stubTwoFactorProviderCredentialsAreValid(MockObject $provider, bool $isValid): void
    {
        $provider
            ->expects($this->any())
            ->method('validateAuthenticationCode')
            ->willReturn($isValid);
    }

    private function stubProviderPreparationComplete(): void
    {
        $this->preparationRecorder
            ->expects($this->any())
            ->method('isProviderPrepared')
            ->willReturn(true);
    }

    /**
     * @test
     */
    public function authenticate_noTwoFactorToken_throwAuthenticationException(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createMock(TokenInterface::class);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The token is not supported by this authentication provider');

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_differentFirewallName_throwAuthenticationException(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createTwoFactorToken('otherFirewallName', 'credentials');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The token is not supported by this authentication provider');

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_noCredentials_returnSameToken(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createTwoFactorToken(self::FIREWALL_NAME, null);

        $returnValue = $this->authenticationProvider->authenticate($token);
        $this->assertSame($token, $returnValue);
    }

    /**
     * @test
     */
    public function authenticate_providerNotPrepared_throwAuthenticationException(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['providerName']);
        $this->preparationRecorder
            ->expects($this->any())
            ->method('isProviderPrepared')
            ->with(self::FIREWALL_NAME, 'providerName')
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The two-factor provider "providerName" has not been prepared.');

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_twoFactorProviderMissing_throwTwoFactorProviderNotFoundException(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['unknownProvider']);
        $this->stubProviderPreparationComplete();

        $this->expectException(TwoFactorProviderNotFoundException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_twoFactorProviderExists_checkCode(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1']);
        $this->stubProviderPreparationComplete();

        $this->twoFactorProvider1
            ->expects($this->once())
            ->method('validateAuthenticationCode')
            ->willReturn(true);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_backupCodeValid_invalidateBackupCode(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1']);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider1, false);
        $this->stubProviderPreparationComplete();

        $this->backupCodeManager
            ->expects($this->once())
            ->method('isBackupCode')
            ->with($this->user, 'credentials')
            ->willReturn(true);

        $this->backupCodeManager
            ->expects($this->once())
            ->method('invalidateBackupCode')
            ->with($this->user, 'credentials');

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_backupCodeInvalid_throwInvalidTwoFactorCodeException(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1']);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider1, false);
        $this->stubProviderPreparationComplete();

        $this->backupCodeManager
            ->expects($this->once())
            ->method('isBackupCode')
            ->willReturn(false);

        $this->expectException(InvalidTwoFactorCodeException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function authenticate_noMultiFactorAuthentication_returnAuthenticatedToken(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(false);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1', 'provider2']);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider1, true);
        $this->stubProviderPreparationComplete();

        $returnValue = $this->authenticationProvider->authenticate($token);
        $this->assertSame($this->authenticatedToken, $returnValue);
    }

    /**
     * @test
     */
    public function authenticate_multiFactorAuthenticationNotComplete_returnTwoFactorToken(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(true);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1', 'provider2']);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider1, true);
        $this->stubProviderPreparationComplete();

        $returnValue = $this->authenticationProvider->authenticate($token);
        $this->assertSame($token, $returnValue);
        $this->assertEquals('provider2', $token->getCurrentTwoFactorProvider());
        $this->assertFalse($token->allTwoFactorProvidersAuthenticated());
    }

    /**
     * @test
     */
    public function authenticate_multiFactorAuthenticationIsComplete_returnAuthenticatedToken(): void
    {
        $this->createAuthenticationProviderWithMultiFactor(true);
        $token = $this->createSupportedTwoFactorTokenWithProviders(['provider1', 'provider2']);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider1, true);
        $this->stubTwoFactorProviderCredentialsAreValid($this->twoFactorProvider2, true);
        $this->stubProviderPreparationComplete();

        $this->authenticationProvider->authenticate($token); // Two-factor provider 1 successful
        $returnValue = $this->authenticationProvider->authenticate($token); // Two-factor provider 2 successful

        $this->assertSame($this->authenticatedToken, $returnValue);
    }
}
