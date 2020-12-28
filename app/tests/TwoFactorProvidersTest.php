<?php

declare(strict_types=1);

namespace App\Tests;

class TwoFactorProvidersTest extends TestCase
{
    public function testProviderEmail(): void
    {
        $this->setIndividual2faProvidersEnabled(true, false, false);
        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $code = $this->getEmailCode();
        $this->submit2faCode($pageAfterLogin, $code);

        $this->assertUserIsFullyAuthenticated();
    }

    public function testProviderGoogleAuthenticator(): void
    {
        $this->setIndividual2faProvidersEnabled(false, true, false);
        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $code = $this->getGoogleAuthenticatorCode();
        $this->submit2faCode($pageAfterLogin, $code);

        $this->assertUserIsFullyAuthenticated();
    }

    public function testProviderTotp(): void
    {
        $this->setIndividual2faProvidersEnabled(false, false, true);
        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $code = $this->getTotpCode();
        $this->submit2faCode($pageAfterLogin, $code);

        $this->assertUserIsFullyAuthenticated();
    }

    public function testProviderPreparedOnLogin(): void
    {
        $this->setIndividual2faProvidersEnabled(true, false, false);
        $this->followRedirects(false); // Assert logs from the last request

        $this->performLogin();

        $this->assertLoggerHasInfo('Two-factor provider "email" prepared');
    }

    public function testProviderPreparedOnAccessDenied(): void
    {
        $this->setIndividual2faProvidersEnabled(true, false, false);
        $this->followRedirects(false); // Assert logs from the last request

        $this->performLogin();
        $this->navigateToSecuredPath();

        // Tried to prepare on that request (but was already prepared before)
        $this->assertLoggerHasInfo('Two-factor provider "email" was already prepared');
    }

    public function testProviderPreparedOn2faForm(): void
    {
        $this->setIndividual2faProvidersEnabled(true, false, false);
        $this->followRedirects(false); // Assert logs from the last request

        $this->performLogin();
        $this->navigateTo2faForm();

        // Tried to prepare on that request (but was already prepared before)
        $this->assertLoggerHasInfo('Two-factor provider "email" was already prepared');
    }
}
