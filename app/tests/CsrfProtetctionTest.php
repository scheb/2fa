<?php

declare(strict_types=1);

namespace App\Tests;

class CsrfProtetctionTest extends TestCase
{
    public function testWrongCsrfTokenBlocksAuthentication(): void
    {
        $this->setAll2faProvidersEnabled(true);

        $twoFactorFormPage = $this->performLogin(true); // With rememberMe option
        $pageAfter2fa = $this->submit2faCode($twoFactorFormPage, $this->getBackupCode(), false, 'invalidCsrfCode');

        $this->assertCsrfError($pageAfter2fa);
    }
}
