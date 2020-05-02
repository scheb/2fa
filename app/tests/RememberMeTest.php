<?php

declare(strict_types=1);

namespace App\Tests;

class RememberMeTest extends TestCase
{
    public function testRememberMeCookieSetAfter2fa(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $twoFactorFormPage = $this->performLogin(true); // With rememberMe option
        $this->assertNotHasRememberMeCookieSet(); // Must not be set directly after login

        $this->perform2fa($twoFactorFormPage);

        $this->assertUserIsFullyAuthenticated();
        $this->assertHasRememberMeCookieSet();
    }

    public function test2faSkippedRememberMeCookieSetImmediately(): void
    {
        $this->setAll2faProvidersEnabled(true);
        $this->configureWhitelistedIpAddress();

        $this->performLogin(true); // With rememberMe option

        $this->assertUserIsFullyAuthenticated();
        $this->assertHasRememberMeCookieSet(); // Is immediately set after login
    }
}
