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
}
