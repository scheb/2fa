<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Http\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class LegacyTwoFactorListener implements ListenerInterface
{
    private $twoFactorListener;

    public function __construct(TwoFactorListener $twoFactorListener)
    {
        $this->twoFactorListener = $twoFactorListener;
    }

    public function handle(GetResponseEvent $event)
    {
        ($this->twoFactorListener)($event);
    }
}
