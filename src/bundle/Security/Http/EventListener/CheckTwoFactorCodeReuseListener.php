<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeCheckEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function assert;
use function sha1;

/**
 * @final
 */
class CheckTwoFactorCodeReuseListener implements EventSubscriberInterface
{
    public const LISTENER_PRIORITY = 0;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly mixed $cache = null,
    ) {
    }

    public function checkForCodeReuse(TwoFactorCodeCheckEvent $event): void
    {
        if (null === $this->cache) {
            return;
        }

        $cacheItem = null;
        assert($cacheItem instanceof CacheItemInterface || null === $cacheItem);
        $cacheKey = 'scheb_two_factor_code_reuse.'
            .sha1($event->getUser()->getUserIdentifier().'.'.$event->getCode());

        if ($this->cache instanceof CacheItemPoolInterface) {
            $cacheItem = $this->cache->getItem($cacheKey);
            $cacheItem->expiresAfter(60);
            $cacheItem->set(true);
            $this->cache->save($cacheItem);
        }

        if (!$cacheItem instanceof CacheItemInterface) {
            return;
        }

        if (!$cacheItem->isHit()) {
            return;
        }

        $this->eventDispatcher->dispatch(new TwoFactorCodeReusedEvent($event->getUser(), $event->getCode()));
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [TwoFactorCodeCheckEvent::class => ['checkForCodeReuse', self::LISTENER_PRIORITY]];
    }
}
