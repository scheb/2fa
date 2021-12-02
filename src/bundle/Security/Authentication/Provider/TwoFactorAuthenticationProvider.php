<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Provider;

use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\PreparationRecorderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallConfig;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @final
 */
class TwoFactorAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var TwoFactorFirewallConfig
     */
    private $twoFactorFirewallConfig;

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var BackupCodeManagerInterface|null
     */
    private $backupCodeManager;

    /**
     * @var PreparationRecorderInterface
     */
    private $preparationRecorder;

    public function __construct(
        TwoFactorFirewallConfig $twoFactorFirewallConfig,
        TwoFactorProviderRegistry $providerRegistry,
        ?BackupCodeManagerInterface $backupCodeManager,
        PreparationRecorderInterface $preparationRecorder
    ) {
        $this->twoFactorFirewallConfig = $twoFactorFirewallConfig;
        $this->providerRegistry = $providerRegistry;
        $this->backupCodeManager = $backupCodeManager;
        $this->preparationRecorder = $preparationRecorder;
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof TwoFactorTokenInterface
            && $this->twoFactorFirewallConfig->getFirewallName() === $token->getProviderKey(true);
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        // Keep unauthenticated TwoFactorTokenInterface with no credentials given
        if (null === $token->getCredentials()) {
            return $token;
        }

        /** @var TwoFactorTokenInterface $token */
        $providerName = $token->getCurrentTwoFactorProvider();
        if (!$providerName) {
            throw new AuthenticationException('There is no active two-factor provider.');
        }

        if (!$this->preparationRecorder->isTwoFactorProviderPrepared($token->getProviderKey(true), $providerName)) {
            throw new AuthenticationException(sprintf('The two-factor provider "%s" has not been prepared.', $providerName));
        }

        if ($this->isValidAuthenticationCode($providerName, $token)) {
            $token->setTwoFactorProviderComplete($providerName);
            if ($this->isAuthenticationComplete($token)) {
                $token = $token->getAuthenticatedToken(); // Authentication complete, unwrap the token
            } else {
                $token->eraseCredentials();
            }

            return $token;
        } else {
            throw new InvalidTwoFactorCodeException(InvalidTwoFactorCodeException::MESSAGE);
        }
    }

    private function isValidAuthenticationCode(string $providerName, TwoFactorTokenInterface $token): bool
    {
        $user = $token->getUser();
        if (null === $user) {
            throw new \RuntimeException('Security token must provide a user.');
        }

        $authenticationCode = $token->getCredentials();

        if ($this->isValidTwoFactorCode($user, $providerName, $authenticationCode)) {
            return true;
        }
        if ($this->isValidBackupCode($user, $authenticationCode)) {
            return true;
        }

        return false;
    }

    /**
     * @param object|string $user
     */
    private function isValidTwoFactorCode($user, string $providerName, string $authenticationCode): bool
    {
        try {
            $authenticationProvider = $this->providerRegistry->getProvider($providerName);
        } catch (\InvalidArgumentException $e) {
            $exception = new TwoFactorProviderNotFoundException('Two-factor provider "'.$providerName.'" not found.');
            $exception->setProvider($providerName);
            throw $exception;
        }

        return $authenticationProvider->validateAuthenticationCode($user, $authenticationCode);
    }

    /**
     * @param object|string $user
     */
    private function isValidBackupCode($user, string $authenticationCode): bool
    {
        if ($this->backupCodeManager && $this->backupCodeManager->isBackupCode($user, $authenticationCode)) {
            $this->backupCodeManager->invalidateBackupCode($user, $authenticationCode);

            return true;
        }

        return false;
    }

    private function isAuthenticationComplete(TwoFactorTokenInterface $token): bool
    {
        return !$this->twoFactorFirewallConfig->isMultiFactor() || $token->allTwoFactorProvidersAuthenticated();
    }
}
