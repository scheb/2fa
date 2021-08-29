<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authenticator\Passport;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\TwoFactorPassport;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;

class TwoFactorPassportTest extends TestCase
{
    /**
     * @var MockObject|CredentialsInterface
     */
    private $credentials;

    /**
     * @var MockObject|BadgeInterface
     */
    private $badge;

    /**
     * @var MockObject|TwoFactorTokenInterface
     */
    private $twoFactorToken;

    /**
     * @var TwoFactorPassport
     */
    private $passport;

    protected function setUp(): void
    {
        $this->credentials = $this->createMock(CredentialsInterface::class);
        $this->badge = $this->createMock(BadgeInterface::class);
        $this->twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('username');
        $this->twoFactorToken
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->passport = new TwoFactorPassport(
            $this->twoFactorToken,
            $this->credentials,
            [$this->badge]
        );
    }

    /**
     * @test
     */
    public function getTwoFactorToken_whenCalled_returnTwoFactorToken(): void
    {
        $this->assertSame($this->twoFactorToken, $this->passport->getTwoFactorToken());
    }
}
