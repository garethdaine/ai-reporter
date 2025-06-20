<?php

declare(strict_types=1);

namespace AIReporter\Notifiers;

use AIReporter\Contracts\Notifier;

final class MultiNotifier implements Notifier
{
    /** @param Notifier[] $notifiers */
    public function __construct(private array $notifiers) {}

    public function send(string $message, string $title = 'Report'): void
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->send($message, $title);
        }
    }
}
