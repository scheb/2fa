<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Tests\EventDispatcherTestHelper;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\AuthenticationMethodBadge;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function class_exists;

/**
 * @property CheckTwoFactorCodeListener $listener
 */
class CheckTwoFactorCodeListenerTest extends AbstractCheckCodeListenerTestSetup
{
    use EventDispatcherTestHelper;

    private MockObject&TwoFactorProviderRegistry $providerRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerRegistry = $this->createMock(TwoFactorProviderRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new CheckTwoFactorCodeListener(
            $this->preparationRecorder,
            $this->providerRegistry,
            $this->eventDispatcher,
        );
    }

    protected function expectDoNothing(): void
    {
        $this->providerRegistry
            ->expects($this->never())
            ->method($this->anything());
        $this->eventDispatcher
            ->expects($this->never())
            ->method($this->anything());
    }

    private function stubTwoFactorAuthenticationProvider(): MockObject&TwoFactorProviderInterface
    {
        $authenticationProvider = $this->createMock(TwoFactorProviderInterface::class);
        $this->providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->willReturn($authenticationProvider);

        return $authenticationProvider;
    }

    #[Test]
    public function checkPassport_twoFactorProviderNotExists_throwTwoFactorProviderNotFoundException(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new TwoFactorCodeEvent($this->user, self::CODE), TwoFactorAuthenticationEvents::CHECK);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProvider')
            ->with(self::TWO_FACTOR_PROVIDER_ID)
            ->willThrowException(new InvalidArgumentException());

        $this->expectCredentialsUnresolved();
        $this->expectException(TwoFactorProviderNotFoundException::class);

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    #[Test]
    public function checkPassport_validCode_invalidateAndResolveCredentials(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->expectDispatchConsecutiveEvents([
            [new TwoFactorCodeEvent($this->user, self::CODE), TwoFactorAuthenticationEvents::CHECK],
            [new TwoFactorCodeEvent($this->user, self::CODE), TwoFactorAuthenticationEvents::CODE_VALID],
        ]);

        $authenticationProvider = $this->stubTwoFactorAuthenticationProvider();
        $authenticationProvider
            ->expects($this->once())
            ->method('validateAuthenticationCode')
            ->with($this->user, self::CODE)
            ->willReturn(true);

        $this->expectMarkCredentialsResolved();

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    #[Test]
    public function checkPassport_invalidCode_unresolvedCredentials(): void
    {
        $this->stubAllPreconditionsFulfilled();

        $this->expectDispatchConsecutiveEvents([
            [new TwoFactorCodeEvent($this->user, self::CODE), TwoFactorAuthenticationEvents::CHECK],
            [new TwoFactorCodeEvent($this->user, self::CODE), TwoFactorAuthenticationEvents::CODE_INVALID],
        ]);

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

    #[Test]
    public function checkPassport_validCodeOfProviderWithAuthenticationMethod_addAuthenticationMethodBadge(): void
    {
        if (!class_exists(AuthenticationMethodBadge::class)) {
            $this->markTestSkipped('Requires the AuthenticationMethodBadge of Symfony 8.2');
        }

        $passport = $this->stubAllPreconditionsFulfilled();

        $authenticationProvider = $this->createMock(AuthenticationMethodTwoFactorProviderInterface::class);
        $authenticationProvider
            ->expects($this->any())
            ->method('validateAuthenticationCode')
            ->willReturn(true);
        $authenticationProvider
            ->expects($this->any())
            ->method('getAuthenticationMethod')
            ->willReturn('otp');
        $this->providerRegistry
            ->expects($this->any())
            ->method('getProvider')
            ->with(self::TWO_FACTOR_PROVIDER_ID)
            ->willReturn($authenticationProvider);

        $passport
            ->expects($this->once())
            ->method('addBadge')
            ->with(new AuthenticationMethodBadge('otp'));

        $this->listener->checkPassport($this->checkPassportEvent);
    }

    #[Test]
    public function checkPassport_validCodeOfProviderWithoutAuthenticationMethod_noAuthenticationMethodBadge(): void
    {
        $passport = $this->stubAllPreconditionsFulfilled();

        $authenticationProvider = $this->stubTwoFactorAuthenticationProvider();
        $authenticationProvider
            ->expects($this->any())
            ->method('validateAuthenticationCode')
            ->willReturn(true);

        $passport
            ->expects($this->never())
            ->method('addBadge');

        $this->listener->checkPassport($this->checkPassportEvent);
    }
}
