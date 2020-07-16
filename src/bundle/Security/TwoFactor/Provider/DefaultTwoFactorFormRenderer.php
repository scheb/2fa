<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DefaultTwoFactorFormRenderer implements TwoFactorFormRendererInterface
{
    /**
     * @var Environment
     */
    private $twigEnvironment;

    /**
     * @var string
     */
    private $template;

    /**
     * @var array
     */
    private $templateVars;

    public function __construct(Environment $twigRenderer, string $template, array $templateVars = [])
    {
        $this->template = $template;
        $this->twigEnvironment = $twigRenderer;
        $this->templateVars = $templateVars;
    }

    public function renderForm(Request $request, array $templateVars): Response
    {
        $content = $this->twigEnvironment->render($this->template, array_merge($this->templateVars, $templateVars));
        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
