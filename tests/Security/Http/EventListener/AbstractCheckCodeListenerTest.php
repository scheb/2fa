<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Security\Http\EventListener\AbstractCheckCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

abstract class AbstractCheckCodeListenerTest extends TestCase
{
    protected const FIREWALL_NAME = 'firewallName';
    protected const TWO_FACTOR_PROVIDER_ID = 'providerId';
    protected const CODE = '2faCode';

    /**
     * @var MockObject|CheckPassportEvent
     */
    protected $checkPassportEvent;

    /**
     * @var MockObject|PreparationRecorderInterface
     */
    protected $preparationRecorder;

    /**
     * @var MockObject|TwoFactorCodeCredentials
     */
    protected $credentialsBadge;

    /**
     * @var MockObject|UserInterface
     */
    protected $user;

    /**
     * @var AbstractCheckCodeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->requireAtLeastSymfony5_1();
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
        $passport = $this->createMock(TwoFactorPassport::class);
        $token = $this->createTwoFactorToken(self::TWO_FACTOR_PROVIDER_ID);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, false);
        $this->stubPassportHasToken($passport, $token);
        $this->stubPreparationPrepared(true);
    }

    /**
     * @return MockObject|TwoFactorTokenInterface
     */
    private function createTwoFactorToken(?string $currentProvider): MockObject
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getProviderKey')
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

    private function stubPassport(PassportInterface $passport): void
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

    /**
     * @return MockObject|TwoFactorCodeCredentials
     */
    private function stubPassportHasCredentialsBadge(MockObject $passport, bool $isResolved): void
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
    public function checkPassport_noTwoFactorPassport_doNothing(): void
    {
        $passport = $this->createMock(PassportInterface::class);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, false);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_noTwoFactorCodeCredentials_doNothing(): void
    {
        $passport = $this->createMock(TwoFactorPassport::class);

        $this->stubPassport($passport);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_credentialsResolved_doNothing()
    {
        $passport = $this->createMock(TwoFactorPassport::class);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, true);
        $this->stubPreparationPrepared(true);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_noActiveTwoFactorProvider_throwAuthenticationException()
    {
        $passport = $this->createMock(TwoFactorPassport::class);
        $token = $this->createTwoFactorToken(null);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, false);
        $this->stubPassportHasToken($passport, $token);
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
    public function checkPassport_providerNotPrepared_throwAuthenticationException()
    {
        $passport = $this->createMock(TwoFactorPassport::class);
        $token = $this->createTwoFactorToken(self::TWO_FACTOR_PROVIDER_ID);

        $this->stubPassport($passport);
        $this->stubPassportHasCredentialsBadge($passport, false);
        $this->stubPassportHasToken($passport, $token);
        $this->stubPreparationPrepared(false);

        $this->expectDoNothing();
        $this->expectCredentialsUnresolved();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('has not been prepared');
        $this->listener->checkPassport($this->checkPassportEvent);
    }
}
