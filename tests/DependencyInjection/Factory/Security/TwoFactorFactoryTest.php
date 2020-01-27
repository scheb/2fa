<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection\Factory\Security;

use Scheb\TwoFactorBundle\DependencyInjection\Factory\Security\TwoFactorFactory;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Yaml\Parser;

class TwoFactorFactoryTest extends TestCase
{
    private const FIREWALL_NAME = 'firewallName';
    private const DEFAULT_CONFIG = ['config' => 'value'];
    private const USER_PROVIDER = 'userProvider';
    private const DEFAULT_ENTRY_POINT = 'defaultEntryPoint';

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
        $this->factory = new TwoFactorFactory();
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
    csrf_token_generator: security.csrf.token_manager
    csrf_parameter: _custom_csrf_token
    csrf_token_id: custom_two_factor
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
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

    private function callCreateFirewall(array $customConfig = []): array
    {
        return $this->factory->create(
            $this->container,
            self::FIREWALL_NAME,
            array_merge(self::DEFAULT_CONFIG, $customConfig),
            self::USER_PROVIDER,
            self::DEFAULT_ENTRY_POINT
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
        $this->assertNull($processedConfiguration['csrf_token_generator']);
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
        $this->assertEquals('security.csrf.token_manager', $processedConfiguration['csrf_token_generator']);
        $this->assertEquals('_custom_csrf_token', $processedConfiguration['csrf_parameter']);
        $this->assertEquals('custom_two_factor', $processedConfiguration['csrf_token_id']);
    }

    /**
     * @test
     */
    public function create_createForFirewall_returnServiceIds(): void
    {
        $returnValue = $this->callCreateFirewall();
        $expectedListenerId = 'security.authentication.listener.two_factor.firewallName';

        // Symfony < 4.3
        if (!class_exists(RequestEvent::class)) {
            $expectedListenerId = 'security.authentication.legacy_listener.two_factor.firewallName';
        }

        $this->assertEquals('security.authentication.provider.two_factor.firewallName', $returnValue[0]);
        $this->assertEquals($expectedListenerId, $returnValue[1]);
        $this->assertEquals(self::DEFAULT_ENTRY_POINT, $returnValue[2]);
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationProviderDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.provider.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.provider.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(0));
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationListenerDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.listener.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(3));
        $this->assertEquals('security.authentication.success_handler.two_factor.firewallName', (string) $definition->getArgument(4));
        $this->assertEquals('security.authentication.failure_handler.two_factor.firewallName', (string) $definition->getArgument(5));
        $this->assertEquals('security.authentication.authentication_required_handler.two_factor.firewallName', (string) $definition->getArgument(6));
        $this->assertEquals('security.authentication.csrf_token_validator.two_factor.firewallName', (string) $definition->getArgument(7));
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(8));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createSuccessHandlerDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.success_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.success_handler.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(1));
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(2));
    }

    /**
     * @test
     */
    public function create_customSuccessHandler_useCustomSuccessHandlerDefinition(): void
    {
        $this->callCreateFirewall([
            'success_handler' => 'my_success_handler',
        ]);

        $this->assertFalse($this->container->hasDefinition('security.authentication.success_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.listener.two_factor.firewallName');
        $this->assertEquals(new Reference('my_success_handler'), $definition->getArgument(4));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createFailureHandlerDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.failure_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.failure_handler.two_factor.firewallName');
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function create_customFailureHandler_useCustomFailureHandlerDefinition(): void
    {
        $this->callCreateFirewall([
            'failure_handler' => 'my_failure_handler',
        ]);

        $this->assertFalse($this->container->hasDefinition('security.authentication.failure_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.listener.two_factor.firewallName');
        $this->assertEquals(new Reference('my_failure_handler'), $definition->getArgument(5));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationRequiredHandlerDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.authentication_required_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.authentication_required_handler.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(1));
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(2));
    }

    /**
     * @test
     */
    public function create_customAuthenticationRequired_useCustomAuthenticationRequiredHandlerDefinition(): void
    {
        $this->callCreateFirewall([
            'authentication_required_handler' => 'my_authentication_required_handler',
        ]);

        $this->assertFalse($this->container->hasDefinition('security.authentication.authentication_required_handler.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.listener.two_factor.firewallName');
        $this->assertEquals(new Reference('my_authentication_required_handler'), $definition->getArgument(6));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createCsrfTokenValidatorDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.csrf_token_validator.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.csrf_token_validator.two_factor.firewallName');
        $this->assertEquals(new Reference('scheb_two_factor.null_csrf_token_manager'), $definition->getArgument(0));
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function create_createForFirewall_useCustomCsrfTokenGenerator(): void
    {
        $csrfTokenGeneratorDefinitionId = 'security.csrf.token_manager';
        $this->callCreateFirewall(['csrf_token_generator' => $csrfTokenGeneratorDefinitionId]);

        $this->assertTrue($this->container->hasDefinition('security.authentication.csrf_token_validator.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.csrf_token_validator.two_factor.firewallName');
        $this->assertEquals(new Reference($csrfTokenGeneratorDefinitionId), $definition->getArgument(0));
        $this->assertEquals(array_merge(self::DEFAULT_CONFIG, ['csrf_token_generator' => $csrfTokenGeneratorDefinitionId]), $definition->getArgument(1));
    }

    /**
     * @test
     */
    public function create_createForFirewall_createFirewallConfigDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.firewall_config.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.firewall_config.two_factor.firewallName');
        $this->assertEquals(self::DEFAULT_CONFIG, $definition->getArgument(0));
        $this->assertTrue($definition->hasTag('scheb_two_factor.firewall_config'));
        $tag = $definition->getTag('scheb_two_factor.firewall_config');
        $this->assertEquals(['firewall' => 'firewallName'], $tag[0]);
    }

    /**
     * @test
     */
    public function create_createForFirewall_createProviderPreparationListenerDefinition(): void
    {
        $this->callCreateFirewall([
            'prepare_on_login' => true,
            'prepare_on_access_denied' => false,
        ]);

        $this->assertTrue($this->container->hasDefinition('security.authentication.provider_preparation_listener.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.provider_preparation_listener.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(3));
        $this->assertTrue($definition->getArgument(4));
        $this->assertFalse($definition->getArgument(5));
        $tag = $definition->getTag('kernel.event_listener');
        $this->assertCount(4, $tag, 'Must have 4 events subscribed');
        $this->assertEquals(['event' => 'security.authentication.success', 'method' => 'onLogin', 'priority' => PHP_INT_MAX], $tag[0]);
        $this->assertEquals(['event' => 'scheb_two_factor.authentication.require', 'method' => 'onAccessDenied'], $tag[1]);
        $this->assertEquals(['event' => 'scheb_two_factor.authentication.form', 'method' => 'onTwoFactorForm'], $tag[2]);
        $this->assertEquals(['event' => 'kernel.finish_request', 'method' => 'onKernelFinishRequest'], $tag[3]);
    }

    /**
     * @test
     */
    public function create_createForFirewall_createAuthenticationSuccessEventSuppressorDefinition(): void
    {
        $this->callCreateFirewall();

        $this->assertTrue($this->container->hasDefinition('security.authentication.authentication_success_event_suppressor.two_factor.firewallName'));
        $definition = $this->container->getDefinition('security.authentication.authentication_success_event_suppressor.two_factor.firewallName');
        $this->assertEquals(self::FIREWALL_NAME, $definition->getArgument(0));
        $tag = $definition->getTag('kernel.event_listener');
        $this->assertCount(1, $tag, 'Must have 4 events subscribed');
        $this->assertEquals(['event' => 'security.authentication.success', 'method' => 'onLogin', 'priority' => PHP_INT_MAX - 1], $tag[0]);
    }
}

// Helper class to process config
class TestableFactoryConfiguration implements ConfigurationInterface
{
    /**
     * @var TwoFactorFactory
     */
    private $factory;

    public function __construct(TwoFactorFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(TwoFactorFactory::AUTHENTICATION_PROVIDER_KEY);
        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root(TwoFactorFactory::AUTHENTICATION_PROVIDER_KEY);
        }

        $this->factory->addConfiguration($rootNode);

        return $treeBuilder;
    }
}
