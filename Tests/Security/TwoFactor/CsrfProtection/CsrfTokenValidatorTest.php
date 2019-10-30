<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\CsrfProtection;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Csrf\CsrfTokenValidator;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenValidatorTest extends TestCase
{
    private const CSRF_PARAMETER = 'parameter_name';
    private const CSRF_TOKEN_ID = 'token_id';

    /**
     * @var MockObject|ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|CsrfTokenManagerInterface
     */
    private $csrfTokenGenerator;

    /**
     * @var CsrfTokenValidator
     */
    private $csrfTokenValidator;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->parameterBag
            ->expects($this->any())
            ->method('get')
            ->willReturn('token_value');

        $this->request = $this->createMock(Request::class);
        $this->request->request = $this->parameterBag;

        $this->csrfTokenGenerator = $this->createMock(CsrfTokenManagerInterface::class);

        $options = [
            'csrf_parameter' => self::CSRF_PARAMETER,
            'csrf_token_id' => self::CSRF_TOKEN_ID,
        ];

        $this->csrfTokenValidator = new CsrfTokenValidator($this->csrfTokenGenerator, $options);
    }

    private function stubTokenIsInvalid(): void
    {
        $this->csrfTokenGenerator
            ->expects($this->any())
            ->method('isTokenValid')
            ->willReturn(false);
    }

    private function stubTokenIsValid(): void
    {
        $this->csrfTokenGenerator
            ->expects($this->any())
            ->method('isTokenValid')
            ->willReturn(true);
    }

    /**
     * @test
     */
    public function hasValidCsrfToken_tokenIsInvalid_returnFalse(): void
    {
        $this->stubTokenIsInvalid();

        $this->assertFalse($this->csrfTokenValidator->hasValidCsrfToken($this->request));
    }

    /**
     * @test
     */
    public function hasValidCsrfToken_tokenIsValid_returnTrue(): void
    {
        $this->stubTokenIsValid();

        $this->assertTrue($this->csrfTokenValidator->hasValidCsrfToken($this->request));
    }
}
