<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Trusted;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function array_map;
use function explode;
use function implode;

/**
 * @final
 */
class TrustedDeviceTokenStorage
{
    private const TOKEN_DELIMITER = ';';

    /** @var TrustedDeviceToken[] */
    private array|null $trustedTokenList;

    private bool $updateCookie = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TrustedDeviceTokenEncoder $tokenGenerator,
        private readonly string $cookieName,
    ) {
        $this->trustedTokenList = null;
    }

    public function hasUpdatedCookie(): bool
    {
        return $this->updateCookie;
    }

    public function getCookieValue(): string|null
    {
        return implode(self::TOKEN_DELIMITER, array_map(static function (TrustedDeviceToken $token): string {
            return $token->serialize();
        }, $this->getTrustedTokenList()));
    }

    public function hasTrustedToken(string $username, string $firewall, int $version): bool
    {
        foreach ($this->getTrustedTokenList() as $key => $token) {
            if (!$token->authenticatesRealm($username, $firewall)) {
                continue;
            }

            if ($token->versionMatches($version)) {
                return true;
            }

            // Remove the trusted token, because the version is outdated
            /** @psalm-suppress PossiblyNullArrayAccess */
            unset($this->trustedTokenList[$key]);
            $this->updateCookie = true;
        }

        return false;
    }

    public function addTrustedToken(string $username, string $firewall, int $version): void
    {
        foreach ($this->getTrustedTokenList() as $key => $token) {
            if (!$token->authenticatesRealm($username, $firewall)) {
                continue;
            }

            // Remove the trusted token, because it is to be replaced with a newer one
            /** @psalm-suppress PossiblyNullArrayAccess */
            unset($this->trustedTokenList[$key]);
        }

        $this->trustedTokenList[] = $this->tokenGenerator->generateToken($username, $firewall, $version);
        $this->updateCookie = true;
    }

    public function clearTrustedToken(string $username, string $firewall): void
    {
        $found = false;
        foreach ($this->getTrustedTokenList() as $key => $token) {
            if (!$token->authenticatesRealm($username, $firewall)) {
                continue;
            }

            // Remove the trusted token, because it is to be replaced with a newer one
            /** @psalm-suppress PossiblyNullArrayAccess */
            unset($this->trustedTokenList[$key]);
            $found = true;
        }

        if (!$found) {
            return;
        }

        $this->updateCookie = true;
    }

    /**
     * @return TrustedDeviceToken[]
     */
    private function getTrustedTokenList(): array
    {
        if (null === $this->trustedTokenList) {
            $this->trustedTokenList = $this->readTrustedTokenList();
        }

        return $this->trustedTokenList;
    }

    /**
     * @return TrustedDeviceToken[]
     */
    private function readTrustedTokenList(): array
    {
        $cookie = $this->readCookieValue();
        if (!$cookie) {
            return [];
        }

        $trustedTokenList = [];
        $trustedTokenEncodedList = explode(self::TOKEN_DELIMITER, $cookie);
        foreach ($trustedTokenEncodedList as $trustedTokenEncoded) {
            $trustedToken = $this->tokenGenerator->decodeToken($trustedTokenEncoded);
            if (!$trustedToken || $trustedToken->isExpired()) {
                $this->updateCookie = true; // When there are invalid token, update the cookie to remove them
            } else {
                $trustedTokenList[] = $trustedToken;
            }
        }

        return $trustedTokenList;
    }

    private function readCookieValue(): string|null
    {
        $cookieValue = $this->getRequest()->cookies->get($this->cookieName, null);

        return null === $cookieValue ? null : (string) $cookieValue;
    }

    private function getRequest(): Request
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            throw new RuntimeException('No request available');
        }

        return $request;
    }
}
