<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Authentication\Exception;

use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Tests\TestCase;

class InvalidTwoFactorCodeExceptionTest extends TestCase
{
    private const CUSTOM_MESSAGE_KEY = 'custom_message_key';

    /**
     * @test
     */
    public function unserialize_customMessageKey_isUnserialized(): void
    {
        $exception = new InvalidTwoFactorCodeException();
        $exception->setMessageKey(self::CUSTOM_MESSAGE_KEY);

        $unserialized = unserialize(serialize($exception));

        $this->assertEquals(self::CUSTOM_MESSAGE_KEY, $unserialized->getMessageKey());
    }
}
