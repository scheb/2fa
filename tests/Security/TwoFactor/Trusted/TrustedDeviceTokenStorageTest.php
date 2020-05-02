<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TrustedDeviceTokenStorageTest extends TestCase
{
    /**
     * @var MockObject|JwtTokenEncoder
     */
    private $jwtEncoder;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TrustedDeviceTokenStorage
     */
    private $tokenStorage;

    protected function setUp(): void
    {
        $this->jwtEncoder = $this->createMock(JwtTokenEncoder::class);
        $this->request = new Request();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->request);

        $this->tokenStorage = new TestableTrustedDeviceTokenStorage($requestStack, $this->jwtEncoder, 'cookieName', 3600);
        $this->tokenStorage->now = new \DateTimeImmutable('2018-01-01 00:00:00');
    }

    public function stubCookieHasToken(string $serializedTokenList): void
    {
        $this->request->cookies->set('cookieName', $serializedTokenList);
    }

    private function stubGenerateNewToken(MockObject $newToken): void
    {
        $this->jwtEncoder
            ->expects($this->any())
            ->method('generateToken')
            ->willReturn($newToken);
    }

    private function stubDecodeToken(...$serializedValues): void
    {
        $this->jwtEncoder
            ->expects($this->any())
            ->method('decodeToken')
            ->willReturnOnConsecutiveCalls(...$serializedValues);
    }

    private function createTokenWithProperties(string $serializedValue, ?string $username = null, ?string $firewallName = null, ?int $version = null): MockObject
    {
        $jwtToken = $this->createMock(Token::class);
        $jwtToken
            ->expects($this->any())
            ->method('__toString')
            ->willReturn($serializedValue);
        $jwtToken
            ->expects($this->any())
            ->method('getClaim')
            ->willReturnMap([
                [JwtTokenEncoder::CLAIM_USERNAME, false, $username],
                [JwtTokenEncoder::CLAIM_FIREWALL, false, $firewallName],
                [JwtTokenEncoder::CLAIM_VERSION, false, $version],
            ]);

        return $jwtToken;
    }

    /**
     * @test
     */
    public function hasTrustedToken_differentRealm_returnFalse(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1', 'username', 'otherFirewallName', 1),
            $this->createTokenWithProperties('validToken2', 'otherUsername', 'firewallName', 1)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedToken_sameRealDifferentVersion_returnFalse(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1', 'username', 'otherFirewallName', 1),
            $this->createTokenWithProperties('validToken2', 'username', 'firewallName', 1)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 2);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedToken_sameRealmSameVersion_returnTrue(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1', 'username', 'otherFirewallName', 1),
            $this->createTokenWithProperties('validToken2', 'username', 'firewallName', 1)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function addTrustedToken_addNewToken_generateToken(): void
    {
        $this->jwtEncoder
            ->expects($this->once())
            ->method('generateToken')
            ->with('username', 'firewallName', 1, new \DateTime('2018-01-01 01:00:00'));

        $this->tokenStorage->addTrustedToken('username', 'firewallName', 1);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_noTokenCookie_returnFalse(): void
    {
        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_hasInvalidToken_returnTrue(): void
    {
        $this->stubCookieHasToken('validToken;invalidToken');
        $this->stubDecodeToken(
            $this->createMock(Token::class),
            null
        );

        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_allValidToken_returnFalse(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1'),
            $this->createTokenWithProperties('validToken2')
        );

        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_tokenAdded_returnTrue(): void
    {
        $this->tokenStorage->addTrustedToken('username', 'firewallName', 1);
        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_hasTokenCalledWithAllValidToken_returnFalse(): void
    {
        $this->stubCookieHasToken('validToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken', 'username', 'firewallName', 1)
        );

        $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasUpdatedCookie_hasTokenCalledWithInvalidToken_returnTrue(): void
    {
        $this->stubCookieHasToken('differentVersionToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('differentVersionToken', 'username', 'firewallName', 1)
        );

        $this->tokenStorage->hasTrustedToken('username', 'firewallName', 2);
        $returnValue = $this->tokenStorage->hasUpdatedCookie();
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function getCookieValue_hasMultipleToken_returnSerializedToken(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1'),
            $this->createTokenWithProperties('validToken2')
        );

        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken1;validToken2', $returnValue);
    }

    /**
     * @test
     */
    public function getCookieValue_hasInvalidToken_returnSerializedWithoutInvalidToken(): void
    {
        $this->stubCookieHasToken('validToken;invalidToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken'),
            null
        );

        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken', $returnValue);
    }

    /**
     * @test
     */
    public function getCookieValue_addToken_returnSerializedWithNewToken(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1'),
            $this->createTokenWithProperties('validToken2')
        );
        $this->stubGenerateNewToken($this->createTokenWithProperties('newToken'));

        $this->tokenStorage->addTrustedToken('username', 'firewallName', 1);
        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken1;validToken2;newToken', $returnValue);
    }

    /**
     * @test
     */
    public function getCookieValue_refreshExistingToken_returnSerializedWithReplacedToken(): void
    {
        $this->stubCookieHasToken('validToken1;validToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('validToken1', 'username', 'firewallName', 1),
            $this->createTokenWithProperties('validToken2', 'otherUsername', 'firewallName', 1)
        );
        $this->stubGenerateNewToken($this->createTokenWithProperties('newToken', 'username', 'firewallName', 1));

        $this->tokenStorage->addTrustedToken('username', 'firewallName', 1);
        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken2;newToken', $returnValue);
    }

    /**
     * @test
     */
    public function getCookieValue_hasTokenCalledWithInvalidToken_returnSerializedWithoutInvalidToken(): void
    {
        $this->stubCookieHasToken('differentVersionToken;validToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('invalidVersionToken', 'username', 'firewallName', 1),
            $this->createTokenWithProperties('validToken', 'otherUsername', 'firewallName', 1)
        );

        $this->tokenStorage->hasTrustedToken('username', 'firewallName', 2);
        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken', $returnValue);
    }
}

// Make the current DateTime testable
class TestableTrustedDeviceTokenStorage extends TrustedDeviceTokenStorage
{
    public $now;

    protected function getDateTimeNow(): \DateTimeImmutable
    {
        return $this->now;
    }
}
