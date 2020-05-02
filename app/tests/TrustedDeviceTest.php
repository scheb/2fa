<?php

declare(strict_types=1);

namespace App\Tests;

class TrustedDeviceTest extends TestCase
{
    public function testTrustedDeviceCookieSetAfter2fa(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $twoFactorFormPage = $this->performLogin();
        $this->perform2fa($twoFactorFormPage, true); // With trusted option

        $this->assertUserIsFullyAuthenticated();
        $this->assertHasTrustedDeviceCookieSet();
    }

    public function testTrustedDeviceSkips2fa(): void
    {
        $this->setAll2faProvidersEnabled(true);
        $this->addTrustedDeviceCookie();

        $this->performLogin();

        $this->assertUserIsFullyAuthenticated();
    }
}
