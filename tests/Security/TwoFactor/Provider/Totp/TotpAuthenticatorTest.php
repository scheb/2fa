<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider\Totp;

use OTPHP\TOTPInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TotpCodeEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Scheb\TwoFactorBundle\Tests\EventDispatcherTestHelper;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function strlen;

class TotpAuthenticatorTest extends TestCase
{
    use EventDispatcherTestHelper;

    private const string VALID_AUTH_CODE = 'validCode';
    private const string INVALID_AUTH_CODE = 'invalidCode';

    private MockObject&TwoFactorInterface $user;
    private MockObject&TotpFactory $totpFactory;
    private MockObject&TOTPInterface $totp;
    private TotpAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->user = $this->createMock(TwoFactorInterface::class);
        $this->totp = $this->createMock(TOTPInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->totpFactory = $this->createMock(TotpFactory::class);
        $this->totpFactory
            ->expects($this->any())
            ->method('createTotpForUser')
            ->with($this->user)
            ->willReturn($this->totp);

        $this->authenticator = new TotpAuthenticator($this->totpFactory, $this->eventDispatcher, 123);
    }

    #[Test]
    #[DataProvider('provideCheckCodeData')]
    public function checkCode_validateCode_returnBoolean(string $code, bool $expectedReturnValue): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with($code)
            ->willReturn($expectedReturnValue);

        $returnValue = $this->authenticator->checkCode($this->user, $code);
        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    #[Test]
    public function checkCode_validCode_dispatchCheckAndValidEvent(): void
    {
        $this->totp
            ->method('verify')
            ->willReturn(true);

        $event = new TwoFactorCodeEvent($this->user, self::VALID_AUTH_CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, TotpCodeEvents::CHECK],
            [$event, TotpCodeEvents::VALID],
        ]);

        $this->authenticator->checkCode($this->user, self::VALID_AUTH_CODE);
    }

    #[Test]
    public function checkCode_invalidCode_dispatchCheckAndInvalidEvent(): void
    {
        $this->totp
            ->method('verify')
            ->willReturn(false);

        $event = new TwoFactorCodeEvent($this->user, self::INVALID_AUTH_CODE);
        $this->expectDispatchConsecutiveEvents([
            [$event, TotpCodeEvents::CHECK],
            [$event, TotpCodeEvents::INVALID],
        ]);

        $this->authenticator->checkCode($this->user, self::INVALID_AUTH_CODE);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function provideCheckCodeData(): array
    {
        return [
            [self::VALID_AUTH_CODE, true],
            [self::INVALID_AUTH_CODE, false],
        ];
    }

    #[Test]
    public function checkCode_codeWithSpaces_stripSpacesBeforeCheck(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('verify')
            ->with('123456', null, 123)
            ->willReturn(true);

        $this->authenticator->checkCode($this->user, ' 123 456 ');
    }

    #[Test]
    public function getProvisioningUri_getContentForQrCode_returnUri(): void
    {
        $this->totp
            ->expects($this->once())
            ->method('getProvisioningUri')
            ->willReturn('provisioningUri');

        $returnValue = $this->authenticator->getQRContent($this->user);
        $this->assertEquals('provisioningUri', $returnValue);
    }

    #[Test]
    public function generateSecret_getRandomSecretCode_returnString(): void
    {
        $returnValue = $this->authenticator->generateSecret();
        $this->assertEquals(52, strlen($returnValue));
    }
}
