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

    private array $context;

    public function __construct(Environment $twigRenderer, string $template, array $context = [])
    {
        $this->template = $template;
        $this->twigEnvironment = $twigRenderer;
        $this->context = $context;
    }

    public function renderForm(Request $request, array $templateVars): Response
    {
        $content = $this->twigEnvironment->render($this->template, array_merge($this->context, $templateVars));
        $response = new Response();
        $response->setContent($content);

        return $response;
    }
}
