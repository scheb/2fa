<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Validator constraint for the current user's Google Authenticator TOTP code.
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class UserGoogleTotpCode extends Constraint
{
    public const INVALID_TOTP_CODE_ERROR = '6de3acd0-12f5-40eb-a776-2525c4566649';

    protected const ERROR_NAMES = [self::INVALID_TOTP_CODE_ERROR => 'INVALID_TOTP_CODE_ERROR'];

    public string $message = 'code_invalid';
    public string $translationDomain = 'SchebTwoFactorBundle';
    public string $service = 'scheb_two_factor.security.totp.validator.user_google_totp_code';

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
