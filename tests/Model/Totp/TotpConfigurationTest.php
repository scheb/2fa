<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Model\Totp;

use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;

class TotpConfigurationTest extends TestCase
{
    private const SECRET = 'secret';
    private const ALGORITHM = TotpConfiguration::ALGORITHM_SHA1;
    private const PERIOD = 20;
    private const DIGITS = 8;

    /**
     * @test
     */
    public function construct_fullyConfigured_returnValues(): void
    {
        $totpConfig = new TotpConfiguration(self::SECRET, self::ALGORITHM, self::PERIOD, self::DIGITS);
        $this->assertEquals(self::SECRET, $totpConfig->getSecret());
        $this->assertEquals(self::ALGORITHM, $totpConfig->getAlgorithm());
        $this->assertEquals(self::PERIOD, $totpConfig->getPeriod());
        $this->assertEquals(self::DIGITS, $totpConfig->getDigits());
    }

    /**
     * @test
     */
    public function construct_invalidAlgorithm_throwInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TotpConfiguration(self::SECRET, 'invalidAlgorithm', self::PERIOD, self::DIGITS);
    }
}
