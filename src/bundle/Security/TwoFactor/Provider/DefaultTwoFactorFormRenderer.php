<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use function array_merge;

/**
 * @api Part of the bundle's public API, may be extended
 */
class DefaultTwoFactorFormRenderer implements TwoFactorFormRendererInterface
{
    /**
     * @param array<string,mixed> $templateVars
     */
    public function __construct(
        private readonly Environment $twigEnvironment,
        private readonly string $template,
        private readonly array $templateVars = [],
    ) {
    }

    /**
     * @param array<string,mixed> $templateVars
     */
    public function renderForm(Request $request, array $templateVars): Response
    {
        $content = $this->twigEnvironment->render($this->template, array_merge($this->templateVars, $templateVars));
        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
