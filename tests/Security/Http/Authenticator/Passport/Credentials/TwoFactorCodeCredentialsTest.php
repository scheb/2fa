<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\Authenticator\Passport\Credentials;

use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Tests\TestCase;

class TwoFactorCodeCredentialsTest extends TestCase
{
    private const CODE = 'theCode';

    private MockObject|TwoFactorTokenInterface $twoFactorToken;
    private TwoFactorCodeCredentials $credentials;

    protected function setUp(): void
    {
        $this->twoFactorToken = $this->createMock(TwoFactorTokenInterface::class);
        $this->credentials = new TwoFactorCodeCredentials($this->twoFactorToken, self::CODE);
    }

    /**
     * @test
     */
    public function getCode_initialState_returnCode(): void
    {
        $this->assertEquals(self::CODE, $this->credentials->getCode());
    }

    /**
     * @test
     */
    public function getCode_markedResolved_throwLogicException(): void
    {
        $this->credentials->markResolved();
        $this->expectException(\LogicException::class);
        $this->credentials->getCode();
    }

    /**
     * @test
     */
    public function getCode_initialState_returnFalse(): void
    {
        $this->assertFalse($this->credentials->isResolved());
    }

    /**
     * @test
     */
    public function isResolved_markedResolved_returnTrue(): void
    {
        $this->credentials->markResolved();
        $this->assertTrue($this->credentials->isResolved());
    }
}
