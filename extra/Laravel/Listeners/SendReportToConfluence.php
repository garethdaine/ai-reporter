<?php

declare(strict_types=1);

namespace AIReporter\Laravel\Listeners;

use AIReporter\Contracts\Notifier;
use AIReporter\Events\ReportGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendReportToConfluence implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(private Notifier $notifier) {}

    public function handle(ReportGenerated $event): void
    {
        if (! config('reporting.notifications.confluence.enabled')) {
            return;             // disabled in config
        }

        $title = ucfirst($event->type).' Dev Report ('.$event->end->format('Y-m-d').')';

        $this->notifier->send($event->markdown, $title);
    }
}
