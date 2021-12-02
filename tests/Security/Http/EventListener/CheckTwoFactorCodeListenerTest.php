<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;

class CheckTwoFactorCodeListenerTest extends AbstractCheckCodeListenerTest
{
    /**
     * @var MockObject|BackupCodeManagerInterface
     */
    private $providerRegistry;

    /**
     * @var CheckTwoFactorCodeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->listener = new CheckTwoFactorCodeListener($this->preparationRecorder, $this->providerRegistry);
    }

    protected function expectDoNothing(): void
    {
        $this->providerRegistry
            ->expects($this->never())
            ->method($this->anything());
    }

    private function stubTwoFactorAuthenticationProvider(): MockObject|TwoFactorProviderInterface
    {
        $authenticationProvider = $this->createMock(TwoFactorProviderInterface::class);
        $this->providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->willReturn($authenticationProvider);

        return $authenticationProvider;
    }

    /**
     * @test
     */
    public function checkPassport_twoFactorProviderNotExists_throwTwoFactorProviderNotFoundException()
    {
        $this->stubAllPreconditionsFulfilled();

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProvider')
            ->with(self::TWO_FACTOR_PROVIDER_ID)
            ->willThrowException(new \InvalidArgumentException());

        $this->expectCredentialsUnresolved();
        $this->expectException(TwoFactorProviderNotFoundException::class);

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_validCode_invalidateAndResolveCredentials()
    {
        $this->stubAllPreconditionsFulfilled();

        $authenticationProvider = $this->stubTwoFactorAuthenticationProvider();
        $authenticationProvider
            ->expects($this->once())
            ->method('validateAuthenticationCode')
            ->with($this->user, self::CODE)
            ->willReturn(true);

        $this->expectMarkCredentialsResolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    /**
     * @test
     */
    public function checkPassport_invalidCode_unresolvedCredentials()
    {
        $this->stubAllPreconditionsFulfilled();

        $authenticationProvider = $this->stubTwoFactorAuthenticationProvider();
        $authenticationProvider
            ->expects($this->once())
            ->method('validateAuthenticationCode')
            ->with($this->user, self::CODE)
            ->willReturn(false);

        $this->expectCredentialsUnresolved();
        $this->expectException(InvalidTwoFactorCodeException::class);

        $this->listener->checkPassport($this->checkPassportEvent);
    }
}
