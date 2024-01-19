<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\DependencyInjection;

use Scheb\TwoFactorBundle\DependencyInjection\SchebTwoFactorExtension;
use Scheb\TwoFactorBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Parser;
use function array_map;
use function sprintf;

class SchebTwoFactorExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private SchebTwoFactorExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new SchebTwoFactorExtension();

        // Stub services
        $this->container->setDefinition('acme_test.persister', new Definition());
        $this->container->setDefinition('acme_test.mailer', new Definition());
    }

    /**
     * @test
     */
    public function load_emptyConfig_setDefaultValues(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasParameter(null, 'scheb_two_factor.model_manager_name');
        $this->assertHasParameter(false, 'scheb_two_factor.trusted_device.enabled');
        $this->assertHasNotParameter('scheb_two_factor.email.sender_email');
        $this->assertHasNotParameter('scheb_two_factor.email.sender_name');
        $this->assertHasNotParameter('scheb_two_factor.email.template');
        $this->assertHasNotParameter('scheb_two_factor.email.digits');
        $this->assertHasNotParameter('scheb_two_factor.email.expires_after');
        $this->assertHasNotParameter('scheb_two_factor.email.resend_expired');
        $this->assertHasNotParameter('scheb_two_factor.google.server_name');
        $this->assertHasNotParameter('scheb_two_factor.google.issuer');
        $this->assertHasNotParameter('scheb_two_factor.google.template');
        $this->assertHasNotParameter('scheb_two_factor.google.digits');
        $this->assertHasNotParameter('scheb_two_factor.google.leeway');
        $this->assertHasNotParameter('scheb_two_factor.totp.issuer');
        $this->assertHasNotParameter('scheb_two_factor.totp.server_name');
        $this->assertHasNotParameter('scheb_two_factor.totp.leeway');
        $this->assertHasNotParameter('scheb_two_factor.totp.parameters');
        $this->assertHasNotParameter('scheb_two_factor.totp.template');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.lifetime');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.extend_lifetime');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.cookie_name');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.cookie_secure');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.cookie_same_site');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.cookie_domain');
        $this->assertHasNotParameter('scheb_two_factor.trusted_device.cookie_path');
        $this->assertHasParameter([
            'Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken',
            'Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken',
        ], 'scheb_two_factor.security_tokens');
        $this->assertHasParameter([], 'scheb_two_factor.ip_whitelist');
    }

    /**
     * @test
     */
    public function load_fullConfig_setConfigValues(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasParameter('alternative', 'scheb_two_factor.model_manager_name');
        $this->assertHasParameter('me@example.com', 'scheb_two_factor.email.sender_email');
        $this->assertHasParameter('Sender Name', 'scheb_two_factor.email.sender_name');
        $this->assertHasParameter('AcmeTestBundle:Authentication:emailForm.html.twig', 'scheb_two_factor.email.template');
        $this->assertHasParameter(6, 'scheb_two_factor.email.digits');
        $this->assertHasParameter('PT15M', 'scheb_two_factor.email.expires_after');
        $this->assertHasParameter(false, 'scheb_two_factor.email.resend_expired');
        $this->assertHasParameter('Server Name Google', 'scheb_two_factor.google.server_name');
        $this->assertHasParameter('Issuer Google', 'scheb_two_factor.google.issuer');
        $this->assertHasParameter('AcmeTestBundle:Authentication:googleForm.html.twig', 'scheb_two_factor.google.template');
        $this->assertHasParameter(8, 'scheb_two_factor.google.digits');
        $this->assertHasParameter(20, 'scheb_two_factor.google.leeway');
        $this->assertHasParameter('Issuer TOTP', 'scheb_two_factor.totp.issuer');
        $this->assertHasParameter('Server Name TOTP', 'scheb_two_factor.totp.server_name');
        $this->assertHasParameter(30, 'scheb_two_factor.totp.leeway');
        $this->assertHasParameter(['image' => 'http://foo/bar.png'], 'scheb_two_factor.totp.parameters');
        $this->assertHasParameter('AcmeTestBundle:Authentication:totpForm.html.twig', 'scheb_two_factor.totp.template');
        $this->assertHasParameter(true, 'scheb_two_factor.trusted_device.enabled');
        $this->assertHasParameter(2592000, 'scheb_two_factor.trusted_device.lifetime');
        $this->assertHasParameter(true, 'scheb_two_factor.trusted_device.extend_lifetime');
        $this->assertHasParameter('trusted_cookie', 'scheb_two_factor.trusted_device.cookie_name');
        $this->assertHasParameter(true, 'scheb_two_factor.trusted_device.cookie_secure');
        $this->assertHasParameter(null, 'scheb_two_factor.trusted_device.cookie_same_site');
        $this->assertHasParameter('cookie.example.org', 'scheb_two_factor.trusted_device.cookie_domain');
        $this->assertHasParameter('/cookie-path', 'scheb_two_factor.trusted_device.cookie_path');
        $this->assertHasParameter(['Symfony\Component\Security\Core\Authentication\Token\SomeToken'], 'scheb_two_factor.security_tokens');
        $this->assertHasParameter(['127.0.0.1'], 'scheb_two_factor.ip_whitelist');
    }

    /**
     * @test
     */
    public function load_truthyEnvVarBasedConfig_setConfigValues(): void
    {
        $config = $this->getEnvVarBasedConfig(true);
        $this->extension->load([$config], $this->container);

        $this->assertHasParameter(true, 'scheb_two_factor.trusted_device.enabled');
        $this->assertHasDefinition('scheb_two_factor.security.email.provider');
        $this->assertHasAlias('scheb_two_factor.backup_code_manager', 'scheb_two_factor.default_backup_code_manager');
        $this->assertHasDefinition('scheb_two_factor.security.google.provider');
        $this->assertHasDefinition('scheb_two_factor.security.totp.provider');
    }

    /**
     * @test
     */
    public function load_falsyEnvVarBasedConfig_setConfigValues(): void
    {
        $config = $this->getEnvVarBasedConfig(false);
        $this->extension->load([$config], $this->container);

        $this->assertHasParameter(false, 'scheb_two_factor.trusted_device.enabled');
        $this->assertNotHasDefinition('scheb_two_factor.security.email.provider');
        $this->assertNotHasAlias('scheb_two_factor.backup_code_manager');
        $this->assertNotHasDefinition('scheb_two_factor.security.google.provider');
        $this->assertNotHasDefinition('scheb_two_factor.security.totp.provider');
    }

    /**
     * @test
     */
    public function load_offOrFalseStringEnvVarBasedConfig_setConfigValues(): void
    {
        $yaml = <<<'EOF'
trusted_device:
    enabled: "%env(ENABLE_2FA_OFF_STR)%"
backup_codes:
    enabled: "%env(ENABLE_2FA_OFF_STR)%"
email:
    enabled: "%env(ENABLE_2FA_OFF_STR)%"
google:
    enabled: "%env(ENABLE_2FA_OFF_STR)%"
totp:
    enabled: "%env(ENABLE_2FA_OFF_STR)%"
EOF;
        $parser = new Parser();
        $this->extension->load([$parser->parse($yaml)], $this->container);

        $this->assertHasParameter(false, 'scheb_two_factor.trusted_device.enabled');
        $this->assertNotHasDefinition('scheb_two_factor.security.email.provider');
        $this->assertNotHasAlias('scheb_two_factor.backup_code_manager');
        $this->assertNotHasDefinition('scheb_two_factor.security.google.provider');
        $this->assertNotHasDefinition('scheb_two_factor.security.totp.provider');
    }

    /**
     * @test
     */
    public function load_onOrTrueStringEnvVarBasedConfig_setConfigValues(): void
    {
        $yaml = <<<'EOF'
trusted_device:
    enabled: "%env(ENABLE_2FA_ON_STR)%"
backup_codes:
    enabled: "%env(ENABLE_2FA_ON_STR)%"
email:
    enabled: "%env(ENABLE_2FA_ON_STR)%"
google:
    enabled: "%env(ENABLE_2FA_ON_STR)%"
totp:
    enabled: "%env(ENABLE_2FA_ON_STR)%"
EOF;
        $parser = new Parser();
        $this->extension->load([$parser->parse($yaml)], $this->container);

        $this->assertHasParameter(true, 'scheb_two_factor.trusted_device.enabled');
        $this->assertHasDefinition('scheb_two_factor.security.email.provider');
        $this->assertHasAlias('scheb_two_factor.backup_code_manager', 'scheb_two_factor.default_backup_code_manager');
        $this->assertHasDefinition('scheb_two_factor.security.google.provider');
        $this->assertHasDefinition('scheb_two_factor.security.totp.provider');
    }

    /**
     * @test
     */
    public function load_noAuthEnabled_notLoadServices(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        // Google
        $this->assertNotHasDefinition('scheb_two_factor.security.google_authenticator');
        $this->assertNotHasDefinition('scheb_two_factor.security.google.provider');

        // TOTP
        $this->assertNotHasDefinition('scheb_two_factor.security.totp_authenticator');
        $this->assertNotHasDefinition('scheb_two_factor.security.totp.provider');

        // Email
        $this->assertNotHasDefinition('scheb_two_factor.security.email.symfony_auth_code_mailer');
        $this->assertNotHasDefinition('scheb_two_factor.security.email.code_generator');
        $this->assertNotHasDefinition('scheb_two_factor.security.email.provider');
    }

    /**
     * @test
     */
    public function load_googleAuthEnabled_loadGoogleServices(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasDefinition('scheb_two_factor.security.google_totp_factory');
        $this->assertHasDefinition('scheb_two_factor.security.google_authenticator');
        $this->assertHasDefinition('scheb_two_factor.security.google.default_form_renderer');
        $this->assertHasDefinition('scheb_two_factor.security.google.provider');
    }

    /**
     * @test
     */
    public function load_defaultGoogleAuthFormRenderer_hasDefaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['google']['enabled'] = true; // Enable Google Authenticator provider
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.google.form_renderer', 'scheb_two_factor.security.google.default_form_renderer');
    }

    /**
     * @test
     */
    public function load_customGoogleAuthFormRenderer_hasCustomAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.google.form_renderer', 'acme_test.google_form_renderer');
    }

    /**
     * @test
     */
    public function load_totpAuthEnabled_loadTotpFactoryServices(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasDefinition('scheb_two_factor.security.totp_factory');
        $this->assertHasDefinition('scheb_two_factor.security.totp_authenticator');
        $this->assertHasDefinition('scheb_two_factor.security.totp.default_form_renderer');
        $this->assertHasDefinition('scheb_two_factor.security.totp.provider');
    }

    /**
     * @test
     */
    public function load_defaultTotpFormRenderer_hasDefaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['totp']['enabled'] = true; // Enable TOTP provider
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.totp.form_renderer', 'scheb_two_factor.security.totp.default_form_renderer');
    }

    /**
     * @test
     */
    public function load_customTotpFormRenderer_hasCustomAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.totp.form_renderer', 'acme_test.totp_form_renderer');
    }

    /**
     * @test
     */
    public function load_emailAuthEnabled_loadEmailServices(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasDefinition('scheb_two_factor.security.email.symfony_auth_code_mailer');
        $this->assertHasDefinition('scheb_two_factor.security.email.default_code_generator');
        $this->assertHasDefinition('scheb_two_factor.security.email.default_form_renderer');
        $this->assertHasDefinition('scheb_two_factor.security.email.provider');
    }

    /**
     * @test
     */
    public function load_defaultEmailFormRenderer_hasDefaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['email']['enabled'] = true; // Enable email provider
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.email.form_renderer', 'scheb_two_factor.security.email.default_form_renderer');
    }

    /**
     * @test
     */
    public function load_customEmailFormRenderer_hasCustomAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.email.form_renderer', 'acme_test.email_form_renderer');
    }

    /**
     * @test
     */
    public function load_defaultMailer_notSetAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['email']['enabled'] = true; // Enable email provider
        $this->extension->load([$config], $this->container);

        $this->assertNotHasAlias('scheb_two_factor.security.email.auth_code_mailer');
    }

    /**
     * @test
     */
    public function load_customMailer_setAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.email.auth_code_mailer', 'acme_test.mailer');
    }

    /**
     * @test
     */
    public function load_defaultCodeGenerator_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['email']['enabled'] = true; // Enable email provider
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.email.code_generator', 'scheb_two_factor.security.email.default_code_generator');
    }

    /**
     * @test
     */
    public function load_alternativeCodeGenerator_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.security.email.code_generator', 'acme_test.code_generator');
    }

    /**
     * @test
     */
    public function load_defaultPersister_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.persister', 'scheb_two_factor.persister.doctrine');
    }

    /**
     * @test
     */
    public function load_alternativePersister_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.persister', 'acme_test.persister');
    }

    /**
     * @test
     */
    public function load_noCustomCondition_onlyDefaultConditions(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        $this->assertConditionRegistryContains('scheb_two_factor.authenticated_token_condition');
        $this->assertConditionRegistryContains('scheb_two_factor.ip_whitelist_condition');
    }

    /**
     * @test
     */
    public function load_customCondition_registerCondition(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertConditionRegistryContains('scheb_two_factor.authenticated_token_condition');
        $this->assertConditionRegistryContains('scheb_two_factor.ip_whitelist_condition');
        $this->assertConditionRegistryContains('acme_test.two_factor_condition');
    }

    /**
     * @test
     */
    public function load_trustedDeviceFeatureDisabled_defaultHandlerConfiguration(): void
    {
        $config = $this->getFullConfig();
        $config['trusted_device']['enabled'] = false;
        $this->extension->load([$config], $this->container);

        $this->assertConditionRegistryNotContains('scheb_two_factor.trusted_device_condition');
    }

    /**
     * @test
     */
    public function load_trustedDeviceFeatureDisabled_trustedDeviceHandlerConfigured(): void
    {
        $config = $this->getFullConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertConditionRegistryContains('scheb_two_factor.trusted_device_condition');
    }

    /**
     * @test
     */
    public function load_disabledTrustedDeviceManager_noAliasDefined(): void
    {
        $config = $this->getEmptyConfig();
        $config['trusted_device']['enabled'] = false;
        $this->extension->load([$config], $this->container);

        $this->assertNotHasAlias('scheb_two_factor.trusted_device_manager');
    }

    /**
     * @test
     */
    public function load_enabledTrustedDeviceManager_loadTrustedDeviceServices(): void
    {
        $config = $this->getFullConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasDefinition('scheb_two_factor.trusted_jwt_encoder');
        $this->assertHasDefinition('scheb_two_factor.trusted_token_encoder');
        $this->assertHasDefinition('scheb_two_factor.trusted_token_storage');
        $this->assertHasDefinition('scheb_two_factor.trusted_device_condition');
        $this->assertHasDefinition('scheb_two_factor.trusted_cookie_response_listener');
        $this->assertHasDefinition('scheb_two_factor.default_trusted_device_manager');
    }

    /**
     * @test
     */
    public function load_enabledTrustedDeviceManager_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.trusted_device_manager', 'scheb_two_factor.default_trusted_device_manager');
    }

    /**
     * @test
     */
    public function load_alternativeTrustedDeviceManager_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.trusted_device_manager', 'acme_test.trusted_device_manager');
    }

    /**
     * @test
     */
    public function load_defaultEncryptionKey_useKernelSecret(): void
    {
        $config = $this->getEmptyConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $keyService = $this->container->getDefinition('scheb_two_factor.trusted_jwt_encoder.configuration.key');
        $arguments = $keyService->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertEquals('%kernel.secret%', $arguments[0]);
    }

    /**
     * @test
     */
    public function load_encryptionKeySet_useThatKey(): void
    {
        $config = $this->getFullConfig();
        $config['trusted_device']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $keyService = $this->container->getDefinition('scheb_two_factor.trusted_jwt_encoder.configuration.key');
        $arguments = $keyService->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertEquals('encryptionKey', $arguments[0]);
    }

    /**
     * @test
     */
    public function load_disabledBackupCodeManager_noAliasDefined(): void
    {
        $config = $this->getEmptyConfig();
        $config['backup_codes']['enabled'] = false;
        $this->extension->load([$config], $this->container);

        $this->assertNotHasAlias('scheb_two_factor.backup_code_manager');
    }

    /**
     * @test
     */
    public function load_enabledBackupCodeManager_loadBackupCodeServices(): void
    {
        $config = $this->getFullConfig();
        $config['backup_codes']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasDefinition('scheb_two_factor.default_backup_code_manager');
        $this->assertHasDefinition('scheb_two_factor.null_backup_code_manager');
        $this->assertHasDefinition('scheb_two_factor.security.listener.check_backup_code');
    }

    /**
     * @test
     */
    public function load_enabledBackupCodeManager_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $config['backup_codes']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.backup_code_manager', 'scheb_two_factor.default_backup_code_manager');
    }

    /**
     * @test
     */
    public function load_alternativeBackupCodeManager_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $config['backup_codes']['enabled'] = true;
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.backup_code_manager', 'acme_test.backup_code_manager');
    }

    /**
     * @test
     */
    public function load_defaultIpWhitelistProvider_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.ip_whitelist_provider', 'scheb_two_factor.default_ip_whitelist_provider');
    }

    /**
     * @test
     */
    public function load_alternativeIpWhitelistProvider_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.ip_whitelist_provider', 'acme_test.ip_whitelist_provider');
    }

    /**
     * @test
     */
    public function load_defaultTokenFactory_defaultAlias(): void
    {
        $config = $this->getEmptyConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.token_factory', 'scheb_two_factor.default_token_factory');
    }

    /**
     * @test
     */
    public function load_alternativeTokenFactory_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.token_factory', 'acme_test.two_factor_token_factory');
    }

    /**
     * @test
     */
    public function load_alternativeProviderDecider_replaceAlias(): void
    {
        $config = $this->getFullConfig();
        $this->extension->load([$config], $this->container);

        $this->assertHasAlias('scheb_two_factor.provider_decider', 'acme_test.two_factor_provider_decider');
    }

    /**
     * @return array<string,null>|null
     */
    private function getEmptyConfig(): array|null
    {
        $yaml = '';
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function getFullConfig(): array
    {
        $yaml = <<<'EOF'
persister: acme_test.persister
model_manager_name: "alternative"
security_tokens:
    - Symfony\Component\Security\Core\Authentication\Token\SomeToken
ip_whitelist:
    - 127.0.0.1
ip_whitelist_provider: acme_test.ip_whitelist_provider
two_factor_token_factory: acme_test.two_factor_token_factory
two_factor_provider_decider: acme_test.two_factor_provider_decider
two_factor_condition: acme_test.two_factor_condition
trusted_device:
    enabled: true
    manager: acme_test.trusted_device_manager
    lifetime: 2592000
    extend_lifetime: true
    key: encryptionKey
    cookie_name: trusted_cookie
    cookie_secure: true
    cookie_same_site: null
    cookie_domain: cookie.example.org
    cookie_path: /cookie-path
backup_codes:
    enabled: true
    manager: acme_test.backup_code_manager
email:
    enabled: true
    mailer: acme_test.mailer
    code_generator: acme_test.code_generator
    sender_email: me@example.com
    sender_name: Sender Name
    template: AcmeTestBundle:Authentication:emailForm.html.twig
    form_renderer: acme_test.email_form_renderer
    digits: 6
    expires_after: PT15M
    resend_expired: false
google:
    enabled: true
    issuer: Issuer Google
    server_name: Server Name Google
    template: AcmeTestBundle:Authentication:googleForm.html.twig
    form_renderer: acme_test.google_form_renderer
    digits: 8
    leeway: 20
totp:
    enabled: true
    issuer: Issuer TOTP
    server_name: Server Name TOTP
    leeway: 30
    parameters:
        image: http://foo/bar.png
    template: AcmeTestBundle:Authentication:totpForm.html.twig
    form_renderer: acme_test.totp_form_renderer
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function getEnvVarBasedConfig(bool $truthyConfig): array
    {
        if ($truthyConfig) {
            $yaml = <<<'EOF'
trusted_device:
    enabled: "%env(ENABLE_2FA_TRUTHY)%"
backup_codes:
    enabled: "%env(ENABLE_2FA_TRUTHY)%"
email:
    enabled: "%env(ENABLE_2FA_TRUTHY)%"
google:
    enabled: "%env(ENABLE_2FA_TRUTHY)%"
totp:
    enabled: "%env(ENABLE_2FA_TRUTHY)%"
EOF;
        } else {
            $yaml = <<<'EOF'
trusted_device:
    enabled: "%env(ENABLE_2FA_FALSY)%"
backup_codes:
    enabled: "%env(ENABLE_2FA_FALSY)%"
email:
    enabled: "%env(ENABLE_2FA_FALSY)%"
google:
    enabled: "%env(ENABLE_2FA_FALSY)%"
totp:
    enabled: "%env(ENABLE_2FA_FALSY)%"
EOF;
        }

        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function assertHasParameter(mixed $value, string $key): void
    {
        $this->assertEquals($value, $this->container->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    private function assertHasNotParameter(string $key): void
    {
        $this->assertFalse($this->container->hasParameter($key), sprintf('%s parameter is correct', $key));
    }

    private function assertHasDefinition(string $id): void
    {
        $this->assertTrue($this->container->hasDefinition($id), 'Service "'.$id.'" must be defined.');
    }

    private function assertNotHasDefinition(string $id): void
    {
        $this->assertFalse($this->container->hasDefinition($id), 'Service "'.$id.'" must NOT be defined.');
    }

    private function assertHasAlias(string $id, string $aliasId): void
    {
        $this->assertTrue($this->container->hasAlias($id), 'Alias "'.$id.'" must be defined.');
        $alias = $this->container->getAlias($id);
        $this->assertEquals($aliasId, (string) $alias, 'Alias "'.$id.'" must be alias for "'.$aliasId.'".');
    }

    private function assertNotHasAlias(string $id): void
    {
        $this->assertFalse($this->container->hasAlias($id), 'Alias "'.$id.'" must not be defined.');
    }

    private function assertConditionRegistryContains(string $expectedConditionService): void
    {
        $conditionServices = $this->getConditionRegistryServices();
        $this->assertContains($expectedConditionService, $conditionServices);
    }

    private function assertConditionRegistryNotContains(string $expectedConditionService): void
    {
        $conditionServices = $this->getConditionRegistryServices();
        $this->assertNotContains($expectedConditionService, $conditionServices);
    }

    /**
     * @return string[]
     */
    private function getConditionRegistryServices(): array
    {
        $conditionsArgument = $this->container->getDefinition('scheb_two_factor.condition_registry')->getArgument(0);
        $this->assertInstanceOf(IteratorArgument::class, $conditionsArgument);

        return array_map(static function (Reference $serviceReference) {
            return (string) $serviceReference;
        }, $conditionsArgument->getValues());
    }
}
