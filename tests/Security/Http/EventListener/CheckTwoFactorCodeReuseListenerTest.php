<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeReuseListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeCheckEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @property CheckTwoFactorCodeListener $listener
 */
class CheckTwoFactorCodeReuseListenerTest extends TestCase
{
    private MockObject|EventDispatcherInterface $eventDispatcher;

    private MockObject|UserInterface $user;

    private const CODE = '123456';

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getUserIdentifier')->willReturn('123');
    }

    protected function expectDoNothing(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method($this->anything());
    }

    #[Test]
    public function nothingHappensWithoutCacheProvider(): void
    {
        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, null);
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeCheckEvent($this->user, self::CODE));
    }

    #[Test]
    public function nothingHappensWithNoneCacheObjectAsCacheProvider(): void
    {
        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, new stdClass());
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeCheckEvent($this->user, self::CODE));
    }

    #[Test]
    public function nothingHappensWithSymfonyCacheProvider_noHit(): void
    {
        $cacheProvider = $this->createMock(CacheInterface::class);
        $cacheProvider->expects($this->once())
            ->method('get')->willReturnCallback(function (string $key, Closure $callback) {
                $this->assertEquals('scheb_two_factor_code_reuse.123.123456', $key);

                return $callback(new CacheItem());
            });

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider);
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeCheckEvent($this->user, self::CODE));
    }

    #[Test]
    public function nothingHappensWithPsrCacheProvider_noHit(): void
    {
        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = new CacheItem();
        $cacheProvider->expects($this->once())
            ->method('getItem')->willReturnCallback(function (string $key) use ($cacheItem) {
                $this->assertEquals('scheb_two_factor_code_reuse.123.123456', $key);
                $cacheItem->set(true);
                $cacheItem->expiresAfter(60);

                return $cacheItem;
            });

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider);
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeCheckEvent($this->user, self::CODE));
    }

    #[Test]
    public function codeReusedEventIsDispatchedWithCacheHit(): void
    {
        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);

        $cacheProvider->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new TwoFactorCodeReusedEvent($this->user, self::CODE));

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider);

        $listener->checkForCodeReuse(new TwoFactorCodeCheckEvent($this->user, self::CODE));
    }
}
