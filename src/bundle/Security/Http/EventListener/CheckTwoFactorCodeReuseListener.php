<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\EventListener;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeCheckEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorCodeReusedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function sha1;

/**
 * @final
 */
class CheckTwoFactorCodeReuseListener implements EventSubscriberInterface
{
    public const LISTENER_PRIORITY = 0;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly mixed $cache,
        private readonly int $cacheDuration,
        private readonly LoggerInterface|null $logger,
    ) {
    }

    public function checkForCodeReuse(TwoFactorCodeCheckEvent $event): void
    {
        if (null === $this->cache) {
            return;
        }

        if (! $this->cache instanceof CacheItemPoolInterface) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error('Your logger-cache seems to be configured wrongly! Provide a CacheItemPoolInterface as the cache object if you want to disallow reusing 2FA-codes!');
            }
            return;
        }

        $cacheKey = 'scheb_two_factor_code_reuse.'
            .sha1($event->getUser()->getUserIdentifier().'.'.$event->getCode());

        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->expiresAfter($this->cacheDuration);
        $cacheItem->set(true);
        $this->cache->save($cacheItem);

        if ($cacheItem->isHit()) {
            $this->eventDispatcher->dispatch(new TwoFactorCodeReusedEvent($event->getUser(), $event->getCode()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [TwoFactorCodeCheckEvent::class => ['checkForCodeReuse', self::LISTENER_PRIORITY]];
    }
}
