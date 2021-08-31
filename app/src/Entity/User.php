<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Scheb\TwoFactorBundle\Model\Webauthn\WebauthnTwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User implements UserInterface, \Serializable, EmailTwoFactorInterface, GoogleTwoFactorInterface, TotpTwoFactorInterface, TrustedDeviceInterface, BackupCodeInterface, WebauthnTwoFactorInterface
{
    private const BACKUP_CODES = [111, 222];
    public const TRUSTED_TOKEN_VERSION = 1;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $emailAuthenticationEnabled = true;

    /**
     * @var string
     * @ORM\Column(type="integer")
     */
    private $emailAuthenticationCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $googleAuthenticatorEnabled = true;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $googleAuthenticatorSecret;

    /**
     * @ORM\Column(type="boolean")
     */
    private $totpAuthenticationEnabled = true;

    /**
     * @var string
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="PublicKeyCredentialSource", mappedBy="userHandle")
     */
    private $webauthnSources;

    public function __construct()
    {
        $this->webauthnSources = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return hash('sha256', sprintf('%s-%d', $this->getUsername(), $this->getId()));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials()
    {
    }

    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
            $this->isActive,
        ]);
    }

    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
            $this->isActive) = unserialize($serialized);
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
        return $this->googleAuthenticatorEnabled && (bool) $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
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

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->googleAuthenticatorSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function isBackupCode(string $code): bool
    {
        return \in_array($code, self::BACKUP_CODES);
    }

    public function invalidateBackupCode(string $code): void
    {
    }

    public function getTrustedTokenVersion(): int
    {
        return self::TRUSTED_TOKEN_VERSION;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function isWebauthnAuthenticationEnabled(): bool
    {
        return $this->webauthnSources->isEmpty();
    }

    public function getWebauthnCredentialSources(): array
    {
        return $this->webauthnSources->toArray();
    }

    public function getWebauthnUsername(): string
    {
        return $this->getUsername();
    }

    public function getWebauthnUserId(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Not used in this example.
     * Should be something like "John Doe" or "Skywalker-1977".
     */
    public function getWebauthnDisplayName(): string
    {
        return $this->getUsername();
    }

    /**
     * Not used in this example.
     * Should be a secured image that uses the data: scheme.
     */
    public function getWebauthnIcon(): ?string
    {
        return null;
    }
}
