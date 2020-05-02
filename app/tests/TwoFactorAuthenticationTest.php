<?php

declare(strict_types=1);

namespace App\Tests;

class TwoFactorAuthenticationTest extends TestCase
{
    public function test2faDisabled(): void
    {
        $this->setAll2faProvidersEnabled(false);

        $this->performLogin();

        $this->assertUserIsFullyAuthenticated();
    }

    public function test2faEnforced(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);
    }

    public function test2faInvalidCode(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $pageAfterLogin = $this->performLogin();
        $this->assert2faIsRequired($pageAfterLogin);

        $pageAfter2fa = $this->submit2faCode($pageAfterLogin, 'Invalid code');
        $this->assert2faIsRequired($pageAfter2fa);
        $this->assertInvalidCodeErrorMessage($pageAfter2fa);
    }

    public function testWhitelistedIpSkips2fa(): void
    {
        $this->setAll2faProvidersEnabled(true);
        $this->configureWhitelistedIpAddress();

        $this->performLogin();

        $this->assertUserIsFullyAuthenticated();
    }
}
