<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function in_array;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EmailTwoFactorInterface, GoogleTwoFactorInterface, TotpTwoFactorInterface, TrustedDeviceInterface, BackupCodeInterface
{
    private const BACKUP_CODES = [111, 222];
    public const TRUSTED_TOKEN_VERSION = 1;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 25, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 64)]
    private string $password;

    #[ORM\Column(type: 'string', length: 60, unique: true)]
    private string $email;

    #[ORM\Column(type: 'boolean')]
    private bool $emailAuthenticationEnabled = true;

    #[ORM\Column(type: 'integer')]
    private string|null $emailAuthenticationCode = null;

    #[ORM\Column(type: 'boolean')]
    private bool $googleAuthenticatorEnabled = true;

    #[ORM\Column(type: 'string')]
    private string|null $googleAuthenticatorSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $totpAuthenticationEnabled = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSalt(): string|null
    {
        return null;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return mixed[]
     */
    public function __serialize(): array
    {
        return [
            $this->id,
            $this->username,
            $this->password,
        ];
    }

    /**
     * @param mixed[] $unserialized
     */
    public function __unserialize(array $unserialized): void
    {
        [
            $this->id,
            $this->username,
            $this->password,
        ] = $unserialized;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function setEmailAuthEnabled(bool $emailAuthEnabled): void
    {
        $this->emailAuthenticationEnabled = $emailAuthEnabled;
    }

    public function isEmailAuthEnabled(): bool
    {
        return $this->emailAuthenticationEnabled;
    }

    public function getEmailAuthCode(): string
    {
        return (string) $this->emailAuthenticationCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->emailAuthenticationCode = $authCode;
    }

    public function setGoogleAuthenticatorEnabled(bool $googleAuthenticatorEnabled): void
    {
        $this->googleAuthenticatorEnabled = $googleAuthenticatorEnabled;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorEnabled && $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(string|null $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    public function setTotpAuthenticationEnabled(bool $totpAuthenticationEnabled): void
    {
        $this->totpAuthenticationEnabled = $totpAuthenticationEnabled;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpAuthenticationEnabled && (bool) $this->googleAuthenticatorSecret;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->username;
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface|null
    {
        return new TotpConfiguration($this->googleAuthenticatorSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function isBackupCode(string $code): bool
    {
        return in_array($code, self::BACKUP_CODES);
    }

    public function invalidateBackupCode(string $code): void
    {
    }

    public function getTrustedTokenVersion(): int
    {
        return self::TRUSTED_TOKEN_VERSION;
    }
}
