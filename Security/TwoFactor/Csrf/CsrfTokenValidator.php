<?php

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Csrf;

use Scheb\TwoFactorBundle\Security\Http\ParameterBagUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenValidator
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenGenerator;

    /**
     * @var array
     */
    private $options;

    public function __construct(CsrfTokenManagerInterface $csrfTokenGenerator, array $options)
    {
        $this->csrfTokenGenerator = $csrfTokenGenerator;
        $this->options = $options;
    }

    public function hasValidCsrfToken(Request $request): bool
    {
        $tokenValue = ParameterBagUtils::getRequestParameterValue($request, $this->options['csrf_parameter']);
        $token = new CsrfToken($this->options['csrf_token_id'], $tokenValue);
        if (!$this->csrfTokenGenerator->isTokenValid($token)) {
            return false;
        }

        return true;
    }
}
