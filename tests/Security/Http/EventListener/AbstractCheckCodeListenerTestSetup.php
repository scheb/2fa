<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\EventListener\AbstractCheckCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

abstract class AbstractCheckCodeListenerTestSetup extends TestCase
{
    protected const FIREWALL_NAME = 'firewallName';
    protected const TWO_FACTOR_PROVIDER_ID = 'providerId';
    protected const CODE = '2faCode';

    protected MockObject|CheckPassportEvent $checkPassportEvent;
    protected MockObject|PreparationRecorderInterface $preparationRecorder;
    protected MockObject|TwoFactorCodeCredentials $credentialsBadge;
    protected MockObject|UserInterface $user;
    protected AbstractCheckCodeListener $listener;

    protected function setUp(): void
    {
        $this->user = $this->createMock(UserInterface::class);
        $this->checkPassportEvent = $this->createMock(CheckPassportEvent::class);
        $this->preparationRecorder = $this->createMock(PreparationRecorderInterface::class);
    }

    abstract protected function expectDoNothing(): void;

    protected function expectCredentialsUnresolved(): void
    {
        $this->credentialsBadge
            ->expects($this->never())
            ->method('markResolved');
    }

    protected function expectMarkCredentialsResolved(): void
    {
        $this->credentialsBadge
            ->expects($this->once())
            ->method('markResolved');
    }

    protected function stubAllPreconditionsFulfilled(): void
    {
        $passport = $this->createMock(Passport::class);
        $token = $this->createTwoFactorToken(self::TWO_FACTOR_PROVIDER_ID);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, $token, false);
        $this->stubPreparationPrepared(true);
    }

    private function createTwoFactorToken(string|null $currentProvider): MockObject|TwoFactorTokenInterface
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getFirewallName')
            ->willReturn(self::FIREWALL_NAME);
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
        $token
            ->expects($this->any())
            ->method('getCurrentTwoFactorProvider')
            ->willReturn($currentProvider);

        return $token;
    }

    private function stubPassport(Passport $passport): void
    {
        $this->checkPassportEvent
            ->expects($this->any())
            ->method('getPassport')
            ->willReturn($passport);
    }

    private function stubPassportHasToken(MockObject $passport, TwoFactorTokenInterface $token): void
    {
        $passport
            ->expects($this->any())
            ->method('getTwoFactorToken')
            ->willReturn($token);
    }

    private function stubPassportHasCredentialsBadge(MockObject $passport, TokenInterface $token, bool $isResolved): void
    {
        $this->credentialsBadge = $this->createMock(TwoFactorCodeCredentials::class);
        $this->credentialsBadge
            ->expects($this->any())
            ->method('isResolved')
            ->willReturn($isResolved);

        $this->credentialsBadge
            ->expects($this->any())
            ->method('getCode')
            ->willReturn(self::CODE);

        $this->credentialsBadge
            ->expects($this->any())
            ->method('getTwoFactorToken')
            ->willReturn($token);

        $passport
            ->expects($this->any())
            ->method('hasBadge')
            ->with(TwoFactorCodeCredentials::class)
            ->willReturn(true);

        $passport
            ->expects($this->any())
            ->method('getBadge')
            ->with(TwoFactorCodeCredentials::class)
            ->willReturn($this->credentialsBadge);
    }

    private function stubPreparationPrepared(bool $isPrepared): void
    {
        $this->preparationRecorder
            ->expects($this->any())
            ->method('isTwoFactorProviderPrepared')
            ->with(self::FIREWALL_NAME, self::TWO_FACTOR_PROVIDER_ID)
            ->willReturn($isPrepared);
    }

    /**
     * @test
     */
    public function checkPassport_noTwoFactorCodeCredentials_doNothing(): void
    {
        $passport = $this->createMock(Passport::class);

        $this->stubPassport($passport);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_credentialsResolved_doNothing(): void
    {
        $passport = $this->createMock(Passport::class);
        $token = $this->createTwoFactorToken(null);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, $token, true);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_noActiveTwoFactorProvider_throwAuthenticationException(): void
    {
        $passport = $this->createMock(Passport::class);
        $token = $this->createTwoFactorToken(null);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, $token, false);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('no active two-factor provider');
        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_providerNotPrepared_throwAuthenticationException(): void
    {
        $passport = $this->createMock(Passport::class);
        $token = $this->createTwoFactorToken(self::TWO_FACTOR_PROVIDER_ID);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, $token, false);
        $this->stubPreparationPrepared(false);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('has not been prepared');
        $this->listener->checkPassport($this->checkPassportEvent);
    }
}
