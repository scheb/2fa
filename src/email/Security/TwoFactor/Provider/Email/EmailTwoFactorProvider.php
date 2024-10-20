<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailCodeValidated;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\EmailTwoFactorEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function str_replace;

/**
 * @final
 */
class EmailTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private readonly CodeGeneratorInterface $codeGenerator,
        private readonly TwoFactorFormRendererInterface $formRenderer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        // Check if user can do email authentication
        $user = $context->getUser();

        return $user instanceof TwoFactorInterface && $user->isEmailAuthEnabled();
    }

    public function prepareAuthentication(object $user): void
    {
        if (!($user instanceof TwoFactorInterface)) {
            return;
        }

        $this->codeGenerator->generateAndSend($user);
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!($user instanceof TwoFactorInterface)) {
            return false;
        }

        // Strip any user added spaces
        $authenticationCode = str_replace(' ', '', $authenticationCode);

        $isCodeValid = $user->getEmailAuthCode() === $authenticationCode;

        if (false === $isCodeValid) {
            return false;
        }

        $this->eventDispatcher->dispatch(new EmailCodeValidated($user->getEmailAuthRecipient()), EmailTwoFactorEvents::EMAIL_CODE_VALIDATED);

        return true;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }
}
