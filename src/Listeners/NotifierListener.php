<?php

declare(strict_types=1);

namespace AIReporter\Listeners;

use AIReporter\Contracts\Notifier;
use AIReporter\Events\ReportGenerated;

/**
 * A single listener that pipes ReportGenerated → Notifier::send().
 * Works with any Notifier (Slack, Email, etc.).
 */
final readonly class NotifierListener
{
    public function __construct(private Notifier $notifier) {}

    public function __invoke(ReportGenerated $event): void
    {
        $title = ucfirst($event->type).' Dev Report ('.$event->end->format('Y-m-d').')';

        $this->notifier->send($event->markdown, $title);
    }
}
