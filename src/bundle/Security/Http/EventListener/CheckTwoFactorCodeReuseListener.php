<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function sha1;

/**
 * @final
 */
class CheckTwoFactorCodeReuseListener implements EventSubscriberInterface
{
    public const int LISTENER_PRIORITY = 0;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly mixed $cache,
        private readonly int $cacheDuration,
        private readonly LoggerInterface|null $logger,
    ) {
    }

    public function checkForCodeReuse(TwoFactorCodeEvent $event): void
    {
        $cache = $this->getCachePool();
        if (null === $cache) {
            return;
        }

        $cacheItem = $cache->getItem($this->getCacheKey($event));
        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($cacheItem->isHit()) {
            $this->eventDispatcher->dispatch(
                new TwoFactorCodeReusedEvent($event->getUser(), $event->getCode()),
                TwoFactorAuthenticationEvents::CODE_REUSED,
            );
        }
    }

    public function rememberCode(TwoFactorCodeEvent $event): void
    {
        $cache = $this->getCachePool();
        if (null === $cache) {
            return;
        }

        $cacheItem = $cache->getItem($this->getCacheKey($event));
        $cacheItem->expiresAfter($this->cacheDuration);
        $cacheItem->set(true);
        $cache->save($cacheItem);
    }

    private function getCachePool(): CacheItemPoolInterface|null
    {
        if (null === $this->cache) {
            return null;
        }

        if (!$this->cache instanceof CacheItemPoolInterface) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error('Your reuse-cache seems to be configured wrongly! Provide a CacheItemPoolInterface as the cache object if you want to disallow reusing 2FA-codes!');
            }

            return null;
        }

        return $this->cache;
    }

    private function getCacheKey(TwoFactorCodeEvent $event): string
    {
        return 'scheb_two_factor_code_reuse.'
            .sha1($event->getUser()->getUserIdentifier().'.'.$event->getCode());
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::CHECK => ['checkForCodeReuse', self::LISTENER_PRIORITY],
            TwoFactorAuthenticationEvents::CODE_VALID => ['rememberCode', self::LISTENER_PRIORITY],
        ];
    }
}
