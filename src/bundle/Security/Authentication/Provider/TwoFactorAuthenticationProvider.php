<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Provider;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\TwoFactorProviderNotFoundException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderPreparationRecorder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderRegistry;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TwoFactorAuthenticationProvider implements AuthenticationProviderInterface
{
    private const DEFAULT_OPTIONS = [
        'multi_factor' => TwoFactorFactory::DEFAULT_MULTI_FACTOR,
    ];

    /**
     * @var TwoFactorProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var array
     */
    private $options;

    /**
     * @var BackupCodeManagerInterface
     */
    private $backupCodeManager;

    /**
     * @var TwoFactorProviderPreparationRecorder
     */
    private $preparationRecorder;

    public function __construct(
        string $firewallName,
        array $options,
        TwoFactorProviderRegistry $providerRegistry,
        BackupCodeManagerInterface $backupCodeManager,
        TwoFactorProviderPreparationRecorder $preparationRecorder
    ) {
        $this->firewallName = $firewallName;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->providerRegistry = $providerRegistry;
        $this->backupCodeManager = $backupCodeManager;
        $this->preparationRecorder = $preparationRecorder;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof TwoFactorTokenInterface && $this->firewallName === $token->getProviderKey();
    }

    public function authenticate(TokenInterface $token)
    {
        /** @var TwoFactorTokenInterface $token */
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        // Keep unauthenticated TwoFactorTokenInterface with no credentials given
        if (null === $token->getCredentials()) {
            return $token;
        }

        $providerName = $token->getCurrentTwoFactorProvider();

        if (!$this->preparationRecorder->isProviderPrepared($this->firewallName, $providerName)) {
            throw new AuthenticationException(sprintf('The two-factor provider "%s" has not been prepared.', $providerName));
        }

        if ($this->isValidAuthenticationCode($providerName, $token)) {
            $token->setTwoFactorProviderComplete($providerName);
            if ($this->isAuthenticationComplete($token)) {
                $token = $token->getAuthenticatedToken(); // Authentication complete, unwrap the token
            }

            return $token;
        } else {
            $exception = new InvalidTwoFactorCodeException('Invalid two-factor authentication code.');
            $exception->setMessageKey('code_invalid');
            throw $exception;
        }
    }

    private function isValidAuthenticationCode(string $providerName, TwoFactorTokenInterface $token): bool
    {
        $user = $token->getUser();
        $authenticationCode = $token->getCredentials();

        if ($this->isValidTwoFactorCode($user, $providerName, $authenticationCode)) {
            return true;
        }
        if ($this->isValidBackupCode($user, $authenticationCode)) {
            return true;
        }

        return false;
    }

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

    private function isValidBackupCode($user, string $authenticationCode): bool
    {
        if ($this->backupCodeManager->isBackupCode($user, $authenticationCode)) {
            $this->backupCodeManager->invalidateBackupCode($user, $authenticationCode);

            return true;
        }

        return false;
    }

    private function isAuthenticationComplete(TwoFactorTokenInterface $token): bool
    {
        return !$this->options['multi_factor'] || $token->allTwoFactorProvidersAuthenticated();
    }
}
