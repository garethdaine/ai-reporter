<?php

declare(strict_types=1);

namespace AIReporter\Support;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Minimal PSR-14 dispatcher — zero deps, good for CLI/tests.
 * Frameworks (Laravel, Symfony) can ignore this and use their own bus.
 */
final class SimpleEventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    /** @var array<string, callable[]> */
    private array $listeners = [];

    /* ---------- Listener registration ---------------------------------- */

    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /* ---------- PSR-14 -------------------------------------------------- */

    public function getListenersForEvent(object $event): iterable
    {
        yield from $this->listeners[$event::class] ?? [];
    }

    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }
}
