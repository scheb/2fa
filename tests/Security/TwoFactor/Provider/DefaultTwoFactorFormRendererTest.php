<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class DefaultTwoFactorFormRendererTest extends TestCase
{
    private const TEMPLATE = 'template.html.twig';

    private MockObject|Environment $twig;
    private DefaultTwoFactorFormRenderer $formRender;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->formRender = new DefaultTwoFactorFormRenderer($this->twig, self::TEMPLATE, ['defaultVar' => 'defaultValue']);
    }

    /**
     * @test
     */
    public function renderForm_templateVarsGiven_createResponseWithRenderedForm(): void
    {
        $request = $this->createMock(Request::class);
        $templateVars = ['var1' => 'value1', 'var2' => 'value2'];
        $expectedTemplateVars = ['defaultVar' => 'defaultValue', 'var1' => 'value1', 'var2' => 'value2'];

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE, $expectedTemplateVars)
            ->willReturn('<RenderedForm>');

        $returnValue = $this->formRender->renderForm($request, $templateVars);
        $this->assertEquals('<RenderedForm>', $returnValue->getContent());
    }
}
