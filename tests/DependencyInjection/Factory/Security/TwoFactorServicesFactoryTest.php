<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Factory\Security;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwoFactorServicesFactoryTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const DEFAULT_CONFIG = ['config' => 'value'];

    private const AUTH_REQUIRED_HANDLER_ID = 'auth_required_handler_id';
    private const TWO_FACTOR_FIREWALL_CONFIG_ID = 'firewall_config_id';

    private TwoFactorServicesFactory $servicesFactory;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->servicesFactory = new TwoFactorServicesFactory();
        $this->container = new ContainerBuilder();
    }

    /**
     * @test
     */
    public function createSuccessHandler_defaultHandler_createSuccessHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createSuccessHandler(
            $this->container,
            self::FIREWALL_NAME,
            self::DEFAULT_CONFIG,
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('security.authentication.success_handler.two_factor.firewallName', $returnValue);
        $this->assertTrue($this->container->hasDefinition('security.authentication.success_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.success_handler.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function createSuccessHandler_customSuccessHandler_useCustomSuccessHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createSuccessHandler(
            $this->container,
            self::FIREWALL_NAME,
            ['success_handler' => 'my_success_handler'],
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('my_success_handler', $returnValue);
        $this->assertFalse($this->container->hasDefinition('security.authentication.success_handler.two_factor.firewallName'));
    }

    /**
     * @test
     */
    public function createFailureHandler_defaultHandler_createFailureHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createFailureHandler(
            $this->container,
            self::FIREWALL_NAME,
            self::DEFAULT_CONFIG,
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('security.authentication.failure_handler.two_factor.firewallName', $returnValue);
        $this->assertTrue($this->container->hasDefinition('security.authentication.failure_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.failure_handler.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function createFailureHandler_customFailureHandler_useCustomFailureHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createFailureHandler(
            $this->container,
            self::FIREWALL_NAME,
            ['failure_handler' => 'my_failure_handler'],
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('my_failure_handler', $returnValue);
        $this->assertFalse($this->container->hasDefinition('security.authentication.failure_handler.two_factor.firewallName'));
    }

    /**
     * @test
     */
    public function createAuthenticationRequiredHandler_defaultHandler_createAuthenticationRequiredHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createAuthenticationRequiredHandler(
            $this->container,
            self::FIREWALL_NAME,
            self::DEFAULT_CONFIG,
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('security.authentication.authentication_required_handler.two_factor.firewallName', $returnValue);
        $this->assertTrue($this->container->hasDefinition('security.authentication.authentication_required_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.authentication_required_handler.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function createAuthenticationRequiredHandler_customAuthenticationRequired_useCustomAuthenticationRequiredHandlerDefinition(): void
    {
        $returnValue = $this->servicesFactory->createAuthenticationRequiredHandler(
            $this->container,
            self::FIREWALL_NAME,
            ['authentication_required_handler' => 'my_authentication_required_handler'],
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertEquals('my_authentication_required_handler', $returnValue);
        $this->assertFalse($this->container->hasDefinition('security.authentication.authentication_required_handler.two_factor.firewallName'));
    }

    /**
     * @test
     */
    public function getCsrfTokenManagerId_csrfOptionNotSet_useNullCsrfManager(): void
    {
        $returnValue = $this->servicesFactory->getCsrfTokenManagerId([]);
        $this->assertEquals('scheb_two_factor.null_csrf_token_manager', $returnValue);
    }

    /**
     * @test
     */
    public function getCsrfTokenManagerId_csrfDisabled_useNullCsrfManager(): void
    {
        $returnValue = $this->servicesFactory->getCsrfTokenManagerId(['enable_csrf' => false]);
        $this->assertEquals('scheb_two_factor.null_csrf_token_manager', $returnValue);
    }

    /**
     * @test
     */
    public function getCsrfTokenManagerId_csrfEnabled_useCsrfManagerAlias(): void
    {
        $returnValue = $this->servicesFactory->getCsrfTokenManagerId(['enable_csrf' => true]);
        $this->assertEquals('scheb_two_factor.csrf_token_manager', $returnValue);
    }

    /**
     * @test
     */
    public function createTwoFactorFirewallConfig_configGiven_createFirewallConfigDefinition(): void
    {
        $returnValue = $this->servicesFactory->createTwoFactorFirewallConfig(
            $this->container,
            self::FIREWALL_NAME,
            self::DEFAULT_CONFIG
        );

        $this->assertEquals('security.firewall_config.two_factor.firewallName', $returnValue);
        $this->assertTrue($this->container->hasDefinition('security.firewall_config.two_factor.firewallName'));

        $definition = $this->container->getDefinition('security.firewall_config.two_factor.firewallName');
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(0));

        $this->assertTrue($definition->hasTag('scheb_two_factor.firewall_config'));
        $tag = $definition->getTag('scheb_two_factor.firewall_config');
        $this->assertEquals(['firewall' => self::FIREWALL_NAME], $tag[0]);
    }

    /**
     * @test
     */
    public function createProviderPreparationListener_withSettings_createProviderPreparationListenerDefinition(): void
    {
        $this->servicesFactory->createProviderPreparationListener(
            $this->container,
            self::FIREWALL_NAME,
            [
                'prepare_on_login' => true,
                'prepare_on_access_denied' => false,
            ]
        );

        $this->assertTrue($this->container->hasDefinition('security.authentication.provider_preparation_listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.provider_preparation_listener.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(3));
        $this->assertTrue($definition->getArgument(4));
        $this->assertFalse($definition->getArgument(5));
        $tag = $definition->getTag('kernel.event_subscriber');
        $this->assertCount(1, $tag, 'Must have the "kernel.event_subscriber" tag assigned');
    }

    /**
     * @test
     */
    public function create_createForFirewall_createExceptionListener(): void
    {
        $this->servicesFactory->createKernelExceptionListener(
            $this->container,
            self::FIREWALL_NAME,
            self::AUTH_REQUIRED_HANDLER_ID
        );

        $this->assertTrue($this->container->hasDefinition('security.authentication.kernel_exception_listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.kernel_exception_listener.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(0));
        $this->assertEquals(new Reference(self::AUTH_REQUIRED_HANDLER_ID), $definition->getArgument(2));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAccessListener(): void
    {
        $this->servicesFactory->createAccessListener(
            $this->container,
            self::FIREWALL_NAME,
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertTrue($this->container->hasDefinition('security.authentication.access_listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.access_listener.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(0));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createFormListener(): void
    {
        $this->servicesFactory->createFormListener(
            $this->container,
            self::FIREWALL_NAME,
            self::TWO_FACTOR_FIREWALL_CONFIG_ID
        );

        $this->assertTrue($this->container->hasDefinition('security.authentication.form_listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.form_listener.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(0));
        $tag = $definition->getTag('kernel.event_subscriber');
        $this->assertCount(1, $tag, 'Must have the "kernel.event_subscriber" tag assigned');
    }
}
