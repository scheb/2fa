<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Factory\Security;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorServicesFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Parser;

class TwoFactorFactoryTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const DEFAULT_CONFIG = ['config' => 'value'];
    private const USER_PROVIDER = 'userProvider';
    private const DEFAULT_ENTRY_POINT = 'defaultEntryPoint';

    private const SUCCESS_HANDLER_ID = 'success_handler_id';
    private const FAILURE_HANDLER_ID = 'failure_handler_id';
    private const AUTH_REQUIRED_HANDLER_ID = 'auth_required_handler_id';
    private const CSRF_TOKEN_MANAGER_ID = 'csrf_token_manager_id';
    private const TWO_FACTOR_FIREWALL_CONFIG_ID = 'firewall_config_id';

    /**
     * @var MockObject|TwoFactorServicesFactory
     */
    private $servicesFactory;

    /**
     * @var TwoFactorFactory
     */
    private $factory;

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp(): void
    {
        $this->servicesFactory = $this->createMock(TwoFactorServicesFactory::class);
        $this->factory = new TwoFactorFactory($this->servicesFactory);
        $this->container = new ContainerBuilder();
        $this->container->setDefinition('scheb_two_factor.firewall_context', new Definition());
    }

    private function getEmptyConfig(): ?array
    {
        $yaml = 'two_factor: ~';
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getFullConfig(): array
    {
        $yaml = <<<EOF
two_factor:
    check_path: /check_path
    post_only: false
    auth_form_path: /auth_form_path
    always_use_default_target_path: true
    default_target_path: /default_target_path
    success_handler: my_success_handler
    failure_handler: my_failure_handler
    authentication_required_handler: my_authentication_required_handler
    auth_code_parameter_name: auth_code_param_name
    trusted_parameter_name: trusted_param_name
    multi_factor: true
    prepare_on_login: true
    prepare_on_access_denied: true
    enable_csrf: true
    csrf_parameter: _custom_csrf_token
    csrf_token_id: custom_two_factor
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function stubServicesFactory(): void
    {
        $this->servicesFactory
            ->expects($this->any())
            ->method('createSuccessHandler')
            ->willReturn(self::SUCCESS_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->any())
            ->method('createFailureHandler')
            ->willReturn(self::FAILURE_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->any())
            ->method('createAuthenticationRequiredHandler')
            ->willReturn(self::AUTH_REQUIRED_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->any())
            ->method('getCsrfTokenManagerId')
            ->willReturn(self::CSRF_TOKEN_MANAGER_ID);

        $this->servicesFactory
            ->expects($this->any())
            ->method('createTwoFactorFirewallConfig')
            ->willReturn(self::TWO_FACTOR_FIREWALL_CONFIG_ID);
    }

    private function expectDependingServicesCreated(): void
    {
        $this->servicesFactory
            ->expects($this->once())
            ->method('createSuccessHandler')
            ->with($this->container, self::FIREWALL_NAME, $this->isType('array'), self::TWO_FACTOR_FIREWALL_CONFIG_ID)
            ->willReturn(self::SUCCESS_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createFailureHandler')
            ->with($this->container, self::FIREWALL_NAME, $this->isType('array'), self::TWO_FACTOR_FIREWALL_CONFIG_ID)
            ->willReturn(self::FAILURE_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createAuthenticationRequiredHandler')
            ->with($this->container, self::FIREWALL_NAME, $this->isType('array'), self::TWO_FACTOR_FIREWALL_CONFIG_ID)
            ->willReturn(self::AUTH_REQUIRED_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createTwoFactorFirewallConfig')
            ->with($this->container, self::FIREWALL_NAME, $this->isType('array'))
            ->willReturn(self::TWO_FACTOR_FIREWALL_CONFIG_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createKernelExceptionListener')
            ->with($this->container, self::FIREWALL_NAME, self::AUTH_REQUIRED_HANDLER_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createAccessListener')
            ->with($this->container, self::FIREWALL_NAME, self::TWO_FACTOR_FIREWALL_CONFIG_ID);

        $this->servicesFactory
            ->expects($this->once())
            ->method('createProviderPreparationListener')
            ->with($this->container, self::FIREWALL_NAME, $this->isType('array'));
    }

    private function expectCsrfManagerIdRetrieved(): void
    {
        $this->servicesFactory
            ->expects($this->once())
            ->method('getCsrfTokenManagerId')
            ->with($this->isType('array'))
            ->willReturn(self::CSRF_TOKEN_MANAGER_ID);
    }

    private function processConfiguration(array $config): array
    {
        $firewallConfiguration = new TestableFactoryConfiguration($this->factory);

        // This is to avoid deprecation errors in PHP7.3
        if (method_exists(BaseNode::class, 'setPlaceholderUniquePrefix')) {
            BaseNode::setPlaceholderUniquePrefix('placeholder_prefix');
        }

        return (new Processor())->processConfiguration($firewallConfiguration, $config);
    }

    private function callCreate(array $customConfig = []): array
    {
        return $this->factory->create(
            $this->container,
            self::FIREWALL_NAME,
            array_merge(self::DEFAULT_CONFIG, $customConfig),
            self::USER_PROVIDER,
            self::DEFAULT_ENTRY_POINT
        );
    }

    private function callCreateAuthenticator(array $customConfig = []): string
    {
        return $this->factory->createAuthenticator(
            $this->container,
            self::FIREWALL_NAME,
            array_merge(self::DEFAULT_CONFIG, $customConfig),
            self::USER_PROVIDER
        );
    }

    /**
     * @test
     */
    public function addConfiguration_emptyConfig_setDefaultValues(): void
    {
        $config = $this->getEmptyConfig();
        $processedConfiguration = $this->processConfiguration($config);

        $this->assertEquals(TwoFactorFactory::DEFAULT_CHECK_PATH, $processedConfiguration['check_path']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_POST_ONLY, $processedConfiguration['post_only']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_AUTH_FORM_PATH, $processedConfiguration['auth_form_path']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_ALWAYS_USE_DEFAULT_TARGET_PATH, $processedConfiguration['always_use_default_target_path']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_TARGET_PATH, $processedConfiguration['default_target_path']);
        $this->assertNull($processedConfiguration['success_handler']);
        $this->assertNull($processedConfiguration['failure_handler']);
        $this->assertNull($processedConfiguration['authentication_required_handler']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_AUTH_CODE_PARAMETER_NAME, $processedConfiguration['auth_code_parameter_name']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_TRUSTED_PARAMETER_NAME, $processedConfiguration['trusted_parameter_name']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_MULTI_FACTOR, $processedConfiguration['multi_factor']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_PREPARE_ON_LOGIN, $processedConfiguration['prepare_on_login']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_PREPARE_ON_ACCESS_DENIED, $processedConfiguration['prepare_on_access_denied']);
        $this->assertFalse($processedConfiguration['enable_csrf']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_CSRF_PARAMETER, $processedConfiguration['csrf_parameter']);
        $this->assertEquals(TwoFactorFactory::DEFAULT_CSRF_TOKEN_ID, $processedConfiguration['csrf_token_id']);
    }

    /**
     * @test
     */
    public function addConfiguration_fullConfig_setConfigValues(): void
    {
        $config = $this->getFullConfig();
        $processedConfiguration = $this->processConfiguration($config);

        $this->assertEquals('/check_path', $processedConfiguration['check_path']);
        $this->assertFalse($processedConfiguration['post_only']);
        $this->assertEquals('/auth_form_path', $processedConfiguration['auth_form_path']);
        $this->assertTrue($processedConfiguration['always_use_default_target_path']);
        $this->assertEquals('/default_target_path', $processedConfiguration['default_target_path']);
        $this->assertEquals('my_success_handler', $processedConfiguration['success_handler']);
        $this->assertEquals('my_failure_handler', $processedConfiguration['failure_handler']);
        $this->assertEquals('my_authentication_required_handler', $processedConfiguration['authentication_required_handler']);
        $this->assertEquals('auth_code_param_name', $processedConfiguration['auth_code_parameter_name']);
        $this->assertEquals('trusted_param_name', $processedConfiguration['trusted_parameter_name']);
        $this->assertTrue($processedConfiguration['multi_factor']);
        $this->assertTrue($processedConfiguration['prepare_on_login']);
        $this->assertTrue($processedConfiguration['prepare_on_access_denied']);
        $this->assertTrue($processedConfiguration['enable_csrf']);
        $this->assertEquals('_custom_csrf_token', $processedConfiguration['csrf_parameter']);
        $this->assertEquals('custom_two_factor', $processedConfiguration['csrf_token_id']);
    }

    /**
     * @test
     */
    public function create_createForFirewall_createServices(): void
    {
        $this->expectDependingServicesCreated();
        $this->expectCsrfManagerIdRetrieved();

        $this->callCreate();
    }

    /**
     * @test
     */
    public function create_createForFirewall_returnServiceIds(): void
    {
        $this->stubServicesFactory();
        $returnValue = $this->callCreate();
        $this->assertEquals('security.authentication.provider.two_factor.firewallName', $returnValue[0]);
        $this->assertEquals('security.authentication.listener.two_factor.firewallName', $returnValue[1]);
        $this->assertEquals(self::DEFAULT_ENTRY_POINT, $returnValue[2]);
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationProviderDefinition(): void
    {
        $this->stubServicesFactory();
        $this->callCreate();

        $this->assertTrue($this->container->hasDefinition('security.authentication.provider.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.provider.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(0));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationListenerDefinition(): void
    {
        $this->stubServicesFactory();
        $this->callCreate();

        $this->assertTrue($this->container->hasDefinition('security.authentication.listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.listener.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(2));
        $this->assertEquals(new Reference(self::SUCCESS_HANDLER_ID), (string) $definition->getArgument(3));
        $this->assertEquals(new Reference(self::FAILURE_HANDLER_ID), (string) $definition->getArgument(4));
        $this->assertEquals(new Reference(self::AUTH_REQUIRED_HANDLER_ID), (string) $definition->getArgument(5));
        $this->assertEquals(new Reference(self::CSRF_TOKEN_MANAGER_ID), (string) $definition->getArgument(6));
    }

    /**
     * @test
     */
    public function createAuthenticator_createForFirewall_createServices(): void
    {
        $this->requireAtLeastSymfony5_2();
        $this->expectDependingServicesCreated();
        $this->callCreateAuthenticator();
    }

    /**
     * @test
     */
    public function createAuthenticator_createForFirewall_returnServiceIds(): void
    {
        $this->requireAtLeastSymfony5_2();

        $this->stubServicesFactory();
        $returnValue = $this->callCreateAuthenticator();

        $this->assertEquals('security.authenticator.two_factor.firewallName', $returnValue);
    }

    /**
     * @test
     */
    public function createAuthenticator_createForFirewall_createAuthenticatorDefinition(): void
    {
        $this->requireAtLeastSymfony5_2();

        $this->stubServicesFactory();
        $this->callCreateAuthenticator();

        $this->assertTrue($this->container->hasDefinition('security.authenticator.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authenticator.two_factor.firewallName');
        $this->assertEquals(new Reference(self::TWO_FACTOR_FIREWALL_CONFIG_ID), $definition->getArgument(0));
        $this->assertEquals(new Reference(self::SUCCESS_HANDLER_ID), (string) $definition->getArgument(2));
        $this->assertEquals(new Reference(self::FAILURE_HANDLER_ID), (string) $definition->getArgument(3));
        $this->assertEquals(new Reference(self::AUTH_REQUIRED_HANDLER_ID), (string) $definition->getArgument(4));
    }
}
