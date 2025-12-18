<?php

declare(strict_types=1);

use App\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class ValidatorConstraintsTest extends TestCase
{
    public function testValidatorConstraintsWithWrongCodes(): void
    {
        // Login
        $this->setAll2faProvidersEnabled(false);
        $this->performLogin();

        $submittedPage = $this->submitValidatorForm('wrongCode', 'wrongCode');

        $this->assertValidatedWrongCodes($submittedPage);
    }

    public function testValidatorConstraintsWithCorrectCodes(): void
    {
        // Login
        $this->setAll2faProvidersEnabled(false);
        $this->performLogin();

        $submittedPage = $this->submitValidatorForm($this->getGoogleAuthenticatorCode(), $this->getTotpCode());

        $this->assertValidatedCorrectCodes($submittedPage);
    }

    private function submitValidatorForm(string $googleTotpCode, string $totpCode): Crawler
    {
        $formPage = $this->navigateToValidatorsForm();

        $form = $formPage->selectButton('submit-button')->form();
        $form['form[googleTotpCode]'] = $googleTotpCode;
        $form['form[totpCode]'] = $totpCode;

        return $this->client->submit($form);
    }

    private function assertValidatedWrongCodes(Crawler $submitPage): void
    {
        $this->assertResponseStatusCode(422);  // Unprocessable Entity
        $this->assertStringContainsString(
            'The verification code is not valid',
            $submitPage->html(),
            'The page must show an error message'
        );
    }

    private function assertValidatedCorrectCodes(Crawler $submitPage): void
    {
        $this->assertResponseStatusCode(200);  // Unprocessable Entity
        $this->assertStringContainsString(
            'All codes valid!',
            $submitPage->html(),
            'The page must show the success message'
        );
    }
}
