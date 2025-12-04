<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
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
 * Validator for the `UserTotpCode` constraint.
 *
 * @final
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserTotpCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UserTotpCode) {
            throw new UnexpectedTypeException($constraint, UserTotpCode::class);
        }

        if (null === $value || '' === $value) {
            $this->context->buildViolation($constraint->message)
                ->setCode(UserTotpCode::INVALID_TOTP_CODE_ERROR)
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

        if ($this->totpAuthenticator->checkCode($user, $value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(UserTotpCode::INVALID_TOTP_CODE_ERROR)
            ->setTranslationDomain($constraint->translationDomain)
            ->addViolation();
    }
}
