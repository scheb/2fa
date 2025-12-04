<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validator constraint for the current user's TOTP code.
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class UserTotpCode extends Constraint
{
    public const string INVALID_TOTP_CODE_ERROR = 'f79333fd-6eef-4394-ab6b-54827e2865b6';

    protected const array ERROR_NAMES = [self::INVALID_TOTP_CODE_ERROR => 'INVALID_TOTP_CODE_ERROR'];

    public string $message = 'code_invalid';
    public string $translationDomain = 'SchebTwoFactorBundle';
    public string $service = 'scheb_two_factor.security.totp.validator.user_totp_code';

    #[HasNamedArguments]
    public function __construct(array|null $options = null, string|null $message = null, string|null $translationDomain = null, string|null $service = null, array|null $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->translationDomain = $translationDomain ?? $this->translationDomain;
        $this->service = $service ?? $this->service;
    }

    public function validatedBy(): string
    {
        return $this->service;
    }
}
