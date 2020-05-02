<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Trusted;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TrustedDeviceTokenStorageTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var MockObject|TrustedDeviceTokenEncoder
     */
    private $tokenEncoder;

    /**
     * @var TrustedDeviceTokenStorage
     */
    private $tokenStorage;

    protected function setUp(): void
    {
        $this->tokenEncoder = $this->createMock(TrustedDeviceTokenEncoder::class);
        $this->request = new Request();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->request);

        $this->tokenStorage = new TrustedDeviceTokenStorage($requestStack, $this->tokenEncoder, 'cookieName');
    }

    public function stubCookieHasToken(string $serializedTokenList): void
    {
        $this->request->cookies->set('cookieName', $serializedTokenList);
    }

    private function stubGenerateNewToken(MockObject $newToken): void
    {
        $this->tokenEncoder
            ->expects($this->any())
            ->method('generateToken')
            ->willReturn($newToken);
    }

    private function stubDecodeToken(...$serializedValues): void
    {
        $this->tokenEncoder
            ->expects($this->any())
            ->method('decodeToken')
            ->willReturnOnConsecutiveCalls(...$serializedValues);
    }

    private function createTokenWithProperties(string $serializedValue, bool $authenticatesRealm, bool $versionMatches, bool $isExpired): MockObject
    {
        $jwtToken = $this->createMock(TrustedDeviceToken::class);
        $jwtToken
            ->expects($this->any())
            ->method('serialize')
            ->willReturn($serializedValue);
        $jwtToken
            ->expects($this->any())
            ->method('authenticatesRealm')
            ->willReturn($authenticatesRealm);
        $jwtToken
            ->expects($this->any())
            ->method('versionMatches')
            ->willReturn($versionMatches);
        $jwtToken
            ->expects($this->any())
            ->method('isExpired')
            ->willReturn($isExpired);

        return $jwtToken;
    }

    /**
     * @test
     */
    public function hasTrustedToken_differentRealm_returnFalse(): void
    {
        $this->stubCookieHasToken('serializedToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('serializedToken', false, true, false)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedToken_sameRealmDifferentVersion_returnFalse(): void
    {
        $this->stubCookieHasToken('serializedToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('serializedToken', true, false, false)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedToken_sameRealmSameVersionIsExpired_returnFalse(): void
    {
        $this->stubCookieHasToken('serializedToken');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('serializedToken', true, true, true)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertFalse($returnValue);
    }

    /**
     * @test
     */
    public function hasTrustedToken_sameRealmSameVersion_returnTrue(): void
    {
        $this->stubCookieHasToken('serializedToken1;serializedToken2');
        $this->stubDecodeToken(
            $this->createTokenWithProperties('serializedToken1', false, true, false),
            $this->createTokenWithProperties('serializedToken2', true, true, false)
        );

        $returnValue = $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
        $this->assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function addTrustedToken_addNewToken_generateToken(): void
    {
        $this->tokenEncoder
            ->expects($this->once())
            ->method('generateToken')
            ->with('username', 'firewallName', 1);

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
            $this->createTokenWithProperties('validToken1', true, true, false),
            $this->createTokenWithProperties('validToken2', true, true, false)
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
            $this->createTokenWithProperties('validToken', true, true, false)
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
            $this->createTokenWithProperties('differentVersionToken', true, false, false)
        );

        $this->tokenStorage->hasTrustedToken('username', 'firewallName', 1);
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
            $this->createTokenWithProperties('validToken1', true, true, false),
            $this->createTokenWithProperties('validToken2', true, true, false)
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
            $this->createTokenWithProperties('validToken', true, true, false),
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
            $this->createTokenWithProperties('validToken1', false, true, false),
            $this->createTokenWithProperties('validToken2', false, true, false)
        );
        $this->stubGenerateNewToken($this->createTokenWithProperties('newToken', true, true, false));

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
            $this->createTokenWithProperties('validToken1', true, true, false),
            $this->createTokenWithProperties('validToken2', false, true, false)
        );
        $this->stubGenerateNewToken($this->createTokenWithProperties('newToken', true, true, false));

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
            $this->createTokenWithProperties('validToken', true, true, false)
        );

        $this->tokenStorage->hasTrustedToken('username', 'firewallName', 2);
        $returnValue = $this->tokenStorage->getCookieValue();
        $this->assertEquals('validToken', $returnValue);
    }
}
