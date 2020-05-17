<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Compiler;

use Scheb\TwoFactorBundle\DependencyInjection\Compiler\MailerCompilerPass;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class MailerCompilerPassTest extends TestCase
{
    /**
     * @var MailerCompilerPass
     */
    private $compilerPass;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new MailerCompilerPass();
    }

    private function assertHasAlias($id, $aliasId): void
    {
        $this->assertTrue($this->container->hasAlias($id), 'Alias "'.$id.'" must be defined.');
        $alias = $this->container->getAlias($id);
        $this->assertEquals($aliasId, (string) $alias, 'Alias "'.$id.'" must be alias for "'.$aliasId.'".');
    }

    private function assertNotHasAlias($id): void
    {
        $this->assertFalse($this->container->hasAlias($id), 'Alias "'.$id.'" must not be defined.');
    }

    /**
     * @test
     */
    public function process_emailProviderNotDefined_doNothing(): void
    {
        $this->compilerPass->process($this->container);
        $this->assertNotHasAlias('scheb_two_factor.security.email.auth_code_mailer');
    }

    /**
     * @test
     */
    public function process_aliasAlreadySet_doNothing(): void
    {
        $this->container->setDefinition('scheb_two_factor.security.email.provider', new Definition());
        $this->container->setAlias('scheb_two_factor.security.email.auth_code_mailer', 'some_service');
        $this->compilerPass->process($this->container);
        $this->assertHasAlias('scheb_two_factor.security.email.auth_code_mailer', 'some_service');
    }

    /**
     * @test
     */
    public function process_symfonyMailerAvailable_useIt(): void
    {
        $this->container->setDefinition('scheb_two_factor.security.email.provider', new Definition());
        $this->container->setDefinition('mailer.mailer', new Definition());
        $this->compilerPass->process($this->container);
        $this->assertHasAlias('scheb_two_factor.security.email.auth_code_mailer', 'scheb_two_factor.security.email.symfony_auth_code_mailer');
    }

    /**
     * @test
     */
    public function process_swiftMailerAvailable_useIt(): void
    {
        $this->container->setDefinition('scheb_two_factor.security.email.provider', new Definition());
        $this->container->setDefinition('swiftmailer.mailer.default', new Definition());
        $this->compilerPass->process($this->container);
        $this->assertHasAlias('scheb_two_factor.security.email.auth_code_mailer', 'scheb_two_factor.security.email.swift_auth_code_mailer');
    }

    /**
     * @test
     */
    public function process_bothMailersAvailable_useSymfonyMailer(): void
    {
        $this->container->setDefinition('scheb_two_factor.security.email.provider', new Definition());
        $this->container->setDefinition('mailer.mailer', new Definition());
        $this->container->setDefinition('swiftmailer.mailer.default', new Definition());
        $this->compilerPass->process($this->container);
        $this->assertHasAlias('scheb_two_factor.security.email.auth_code_mailer', 'scheb_two_factor.security.email.symfony_auth_code_mailer');
    }

    /**
     * @test
     */
    public function process_noMailerAvailable_throwLogicException(): void
    {
        $this->container->setDefinition('scheb_two_factor.security.email.provider', new Definition());
        $this->expectException(LogicException::class);
        $this->compilerPass->process($this->container);
    }
}
