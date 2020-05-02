<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenEncoder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class TestCase extends WebTestCase
{
    private const SQLITE_DATA_FILE = __DIR__ . '/../data/data.db';
    private const DEFAULT_IP_ADDRESS = '127.0.0.1';
    private const WHITELISTED_IP_ADDRESS = '127.0.0.2';
    private const USER_NAME = 'user1';
    private const USER_PASSWORD = 'test';
    private const FIREWALL_NAME = 'main';
    private const BACKUP_CODE = '111';
    private const TRUSTED_DEVICE_COOKIE_NAME = 'trusted_device';
    private const REMEMBER_ME_COOKIE_NAME = 'REMEMBERME';

    /**
     * @var KernelBrowser
     */
    private $client;

    ////////////////////// CONFIGURATION

    public function setUp(): void
    {
        parent::setUp();

        $this->resetSqliteDatabase();

        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->client->setServerParameter('REMOTE_ADDR', self::DEFAULT_IP_ADDRESS);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->resetSqliteDatabase();
    }

    protected function configureWhitelistedIpAddress(): void
    {
        $this->client->setServerParameter('REMOTE_ADDR', self::WHITELISTED_IP_ADDRESS);
    }

    protected function setAll2faProvidersEnabled(bool $enabled): void
    {
        $this->setIndividual2faProvidersEnabled($enabled, $enabled, $enabled);
    }

    protected function setIndividual2faProvidersEnabled(bool $emailEnabled, bool $googleAuthenticatorEnabled, bool $totpEnabled): void
    {
        $em = $this->getEntityManager();
        $user = $this->getTestUser($em);

        $user->setEmailAuthEnabled($emailEnabled);
        $user->setGoogleAuthenticatorEnabled($googleAuthenticatorEnabled);
        $user->setTotpAuthenticationEnabled($totpEnabled);

        $em->persist($user);
        $em->flush();
    }

    protected function addTrustedDeviceCookie(): void
    {
        /** @var TrustedDeviceTokenEncoder $tokenEncoder */
        $tokenEncoder = self::$container->get('scheb_two_factor.trusted_token_encoder');
        $token = $tokenEncoder->generateToken(self::USER_NAME, self::FIREWALL_NAME, User::TRUSTED_TOKEN_VERSION);

        $this->client->getCookieJar()->set(new Cookie(
            self::TRUSTED_DEVICE_COOKIE_NAME,
            $token->serialize()
        ));
    }

    protected function getEmailCode(): string
    {
        return $this->getTestUser($this->getEntityManager())->getEmailAuthCode();
    }

    protected function getGoogleAuthenticatorCode(): string
    {
        $user = $this->getTestUser($this->getEntityManager());

        /** @var GoogleTotpFactory $totpFactory */
        $totpFactory = self::$container->get('scheb_two_factor.security.google_totp_factory');
        $totp = $totpFactory->createTotpForUser($user);

        return $totp->at(time());
    }

    protected function getTotpCode(): string
    {
        $user = $this->getTestUser($this->getEntityManager());

        /** @var GoogleTotpFactory $totpFactory */
        $totpFactory = self::$container->get('scheb_two_factor.security.totp_factory');
        $totp = $totpFactory->createTotpForUser($user);

        return $totp->at(time());
    }

    private function resetSqliteDatabase(): void
    {
        exec("git checkout " . self::SQLITE_DATA_FILE . " -q");
    }

    private function getEntityManager(): EntityManager
    {
        $em = self::$container->get('doctrine')->getManager();
        return $em;
    }

    private function getTestUser(EntityManager $em): User
    {
        $repository = $em->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy(['username' => self::USER_NAME]);

        return $user;
    }

    ////////////////////// ACTIONS

    protected function performLogin(bool $rememberMe = false): Crawler
    {
        $loginPage = $this->client->request('GET', '/login');
        $this->assertSuccessResponse();

        $loginForm = $loginPage->selectButton('submit-button')->form();
        $loginForm['_username'] = self::USER_NAME;
        $loginForm['_password'] = self::USER_PASSWORD;
        if ($rememberMe) {
            $loginForm['_remember_me'] = 'on';
        }

        $pageAfterLogin = $this->client->submit($loginForm);
        $this->assertSuccessResponse();

        return $pageAfterLogin;
    }

    protected function perform2fa(Crawler $currentPage, bool $trustedDevice = false): Crawler
    {
        $i = 0;
        while ($currentPage->filter('input#_auth_code')->count() > 0) {
            if (++$i > 10) {
                // Make sure we're not running into an endless loop
                $this->fail('Did not pass 2fa after 10 tries with backup code, something is wrong');
            }

            $currentPage = $this->submit2faCode($currentPage, self::BACKUP_CODE, $trustedDevice);
        }

        return $currentPage;
    }

    protected function submit2faCode(Crawler $currentPage, string $code, bool $trustedDevice = false): Crawler
    {
        $twoFactorForm = $currentPage->filter('input#_auth_code')->parents()->filter('form')->form();
        $twoFactorForm['_auth_code'] = $code;
        if ($trustedDevice && $currentPage->filter('input#_trusted')->count() > 0) {
            $twoFactorForm['_trusted'] = 'on';
        }

        $currentPage = $this->client->submit($twoFactorForm);
        $this->assertSuccessResponse();

        return $currentPage;
    }

    ////////////////////// ASSERTS

    protected function assert2faIsRequired(Crawler $page): void
    {
        $this->assertTrue(
            $page->filter('input#_auth_code')->count() > 0,
            'The page must be the 2fa form page'
        );

        $this->assertInstanceOf(
            TwoFactorToken::class,
            $this->getSecurityToken(),
            'The token has to be a TwoFactorToken'
        );
    }

    protected function assertUserIsFullyAuthenticated(): void
    {
        $this->assertInstanceOf(
            UsernamePasswordToken::class,
            $this->getSecurityToken(),
            'The token has to be a UsernamePasswordToken'
        );
    }

    protected function assertInvalidCodeErrorMessage(Crawler $page): void
    {
        $this->assertStringContainsString('The verification code is not valid', $page->html());
    }

    protected function assertHasRememberMeCookieSet(): void
    {
        $this->assertNotNull($this->client->getCookieJar()->get(self::REMEMBER_ME_COOKIE_NAME), 'Remember-me cookie must be set');
    }

    protected function assertNotHasRememberMeCookieSet(): void
    {
        $this->assertNull($this->client->getCookieJar()->get(self::REMEMBER_ME_COOKIE_NAME), 'Remember-me cookie must NOT be set');
    }

    protected function assertHasTrustedDeviceCookieSet(): void
    {
        $this->assertNotNull($this->client->getCookieJar()->get(self::TRUSTED_DEVICE_COOKIE_NAME), 'Trusted device cookie must be set');
    }

    private function assertSuccessResponse(): void
    {
        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode(),
            'The client must respond with HTTP status 200'
        );
    }

    private function getSecurityToken(): TokenInterface
    {
        return self::$container->get('security.token_storage')->getToken();
    }
}
