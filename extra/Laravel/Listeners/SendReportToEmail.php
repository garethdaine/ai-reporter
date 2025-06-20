<?php

declare(strict_types=1);

namespace AIReporter\Laravel\Listeners;

use AIReporter\Events\ReportGenerated;
use AIReporter\Notifiers\EmailNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendReportToEmail implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(private EmailNotifier $notifier) {}

    public function handle(ReportGenerated $event): void
    {
        // build a title since ReportGenerated has no 'title' property
        $title = ucfirst($event->type).' Dev Report ('.$event->end->format('Y-m-d').')';

        $this->notifier->send(
            message: $event->markdown,
            title: $title
        );
    }
}
