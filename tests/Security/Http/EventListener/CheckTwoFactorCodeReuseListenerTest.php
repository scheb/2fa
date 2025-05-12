<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Tests\Security\Http\EventListener;

use DateInterval;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeReuseListener;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Scheb\TwoFactorBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @property CheckTwoFactorCodeListener $listener
 */
class CheckTwoFactorCodeReuseListenerTest extends TestCase
{
    private MockObject&EventDispatcherInterface $eventDispatcher;

    private MockObject&LoggerInterface $logger;

    private MockObject|UserInterface $user;

    private const MFA_CODE = '123456';

    private const USER_IDENTIFIER = 'jdoe@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getUserIdentifier')->willReturn(self::USER_IDENTIFIER);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function expectDoNothing(): void
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method($this->anything());
    }

    #[Test]
    public function checkForCodeReuse_noCacheProvider_nothingHappens(): void
    {
        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, null, 60, $this->logger);
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeEvent($this->user, self::MFA_CODE));
    }

    #[Test]
    public function checkForCodeReuse_invalidCacheProvider_nothingHappensButLogged(): void
    {
        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, new stdClass(), 60, $this->logger);
        $this->expectDoNothing();
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Your logger-cache seems to be configured wrongly! Provide a CacheItemPoolInterface '
                .'as the cache object if you want to disallow reusing 2FA-codes!',
            );

        $listener->checkForCodeReuse(new TwoFactorCodeEvent($this->user, self::MFA_CODE));
    }

    #[Test]
    public function checkForCodeReuse_validCacheProviderAndNoHit_nothingHappens(): void
    {
        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = new CacheItem();
        $cacheItem->set(true);
        $cacheItem->expiresAfter(60);
        $cacheProvider->expects($this->once())
            ->method('getItem')
            ->with('scheb_two_factor_code_reuse.3468ccbd044e2fdfb0769b68b5c85d7660c6c12c')
            ->willReturn($cacheItem);

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider, 20, $this->logger);
        $this->expectDoNothing();

        $listener->checkForCodeReuse(new TwoFactorCodeEvent($this->user, self::MFA_CODE));
    }

    #[Test]
    public function checkForCodeReuse_validCacheProviderWithCacheHit_eventIsDispatched(): void
    {
        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);

        $cacheProvider->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new TwoFactorCodeReusedEvent($this->user, self::MFA_CODE));

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider, 60, null);

        $listener->checkForCodeReuse(new TwoFactorCodeEvent($this->user, self::MFA_CODE));
    }

    #[Test]
    public function checkForCodeReuse_validCacheProviderNoCacheHit_cacheItemIsSaved(): void
    {
        $cacheItem = new class implements CacheItemInterface {
            private mixed $value;

            public DateTimeInterface|null $expiresAt;

            public DateInterval|int|float|null $expiresAfter;

            public function getKey(): string
            {
                return '';
            }

            public function get(): mixed
            {
                return $this->value;
            }

            public function isHit(): bool
            {
                return false;
            }

            public function set(mixed $value): static
            {
                $this->value = $value;

                return $this;
            }

            public function expiresAt(DateTimeInterface|null $expiration): static
            {
                $this->expiresAt = $expiration;

                return $this;
            }

            public function expiresAfter(DateInterval|int|null $time): static
            {
                $this->expiresAfter = $time;

                return $this;
            }
        };

        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $cacheProvider->expects($this->once())
            ->method('save')
            ->with($cacheItem)
            ->willReturn(true);

        $cacheItem->set(true);
        $cacheItem->expiresAfter(60);
        $cacheProvider->expects($this->once())
            ->method('getItem')
            ->with('scheb_two_factor_code_reuse.3468ccbd044e2fdfb0769b68b5c85d7660c6c12c')
            ->willReturn($cacheItem);

        $listener = new CheckTwoFactorCodeReuseListener($this->eventDispatcher, $cacheProvider, 20, $this->logger);

        $listener->checkForCodeReuse(new TwoFactorCodeEvent($this->user, self::MFA_CODE));

        $this->assertSame(20, $cacheItem->expiresAfter);
        $this->assertSame(true, $cacheItem->get());
    }
}
