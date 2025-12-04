<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * Validator for the `UserGoogleTotpCode` constraint.
 *
 * @final
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserGoogleTotpCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UserGoogleTotpCode) {
            throw new UnexpectedTypeException($constraint, UserGoogleTotpCode::class);
        }

        if (null === $value || '' === $value) {
            $this->context->buildViolation($constraint->message)
                ->setCode(UserGoogleTotpCode::INVALID_TOTP_CODE_ERROR)
                ->setTranslationDomain($constraint->translationDomain)
                ->addViolation();

            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new AuthenticationCredentialsNotFoundException('Could not find Token object for the current user.');
        }

        $user = $token->getUser();

        if (!$user instanceof TwoFactorInterface) {
            throw new ConstraintDefinitionException(sprintf('The "%s" class must implement the "%s" interface.', get_debug_type($user), TwoFactorInterface::class));
        }

        if ($this->googleAuthenticator->checkCode($user, $value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(UserGoogleTotpCode::INVALID_TOTP_CODE_ERROR)
            ->setTranslationDomain($constraint->translationDomain)
            ->addViolation();
    }
}
