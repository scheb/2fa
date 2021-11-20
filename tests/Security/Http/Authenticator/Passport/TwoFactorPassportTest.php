<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authenticator\Passport;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
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

    /**
     * @test
     */
    public function getFirewallName_hasTwoFactorToken_getFromToken(): void
    {
        $this->twoFactorToken
            ->expects($this->any())
            ->method('getProviderKey')
            ->willReturn('firewallName');

        $this->assertEquals('firewallName', $this->passport->getFirewallName());
    }

    /**
     * @test
     */
    public function hasBadge_badgeExists_returnTrue(): void
    {
        $badge = $this->createMock(BadgeInterface::class);
        $this->passport->addBadge($badge);

        $this->assertTrue($this->passport->hasBadge(\get_class($this->credentials)));
        $this->assertTrue($this->passport->hasBadge(\get_class($this->badge)));
        $this->assertTrue($this->passport->hasBadge(\get_class($badge)));
    }

    /**
     * @test
     */
    public function hasBadge_badgeNotExists_returnFalse(): void
    {
        $this->assertFalse($this->passport->hasBadge('unknownBadge'));
    }

    /**
     * @test
     */
    public function getBadge_badgeExists_returnThatBadge(): void
    {
        $badge = $this->createMock(BadgeInterface::class);
        $this->passport->addBadge($badge);

        $returnValue = $this->passport->getBadge(\get_class($badge));
        $this->assertSame($badge, $returnValue);
    }

    /**
     * @test
     */
    public function getBadge_badgeNotExists_returnNull(): void
    {
        $returnValue = $this->passport->getBadge('unknownBadge');
        $this->assertNull($returnValue);
    }

    /**
     * @test
     */
    public function getUser_isInstanceOfUserInterface_returnUserInterface(): void
    {
        $user = $this->createMock(UserInterface::class);
        $this->twoFactorToken
            ->method('getUser')
            ->willReturn($user);

        $currentUser = $this->passport->getUser();

        $this->assertInstanceOf(UserInterface::class, $currentUser);
    }

    /**
     * @test
     */
    public function getUser_returnsString_throwRuntimeException(): void
    {
        $user = 'myusername@example.com';
        $this->twoFactorToken
            ->method('getUser')
            ->willReturn($user);

        $this->expectException(RuntimeException::class);
        $this->passport->getUser();
    }
}
