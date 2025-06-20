<?php

declare(strict_types=1);

namespace AIReporter\Laravel\Listeners;

use AIReporter\Events\ReportGenerated;
use AIReporter\Notifiers\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queueable listener that posts newly-generated reports to Slack.
 *
 * Enable/disable via config:
 *   reporting.notifications.slack.enabled=true|false
 */
final readonly class SendReportToSlack implements ShouldQueue
{
    public int $tries;

    public function __construct(private SlackNotifier $notifier)
    {
        $this->tries = 3;
    }

    public function handle(ReportGenerated $event): void
    {
        if (! config('reporting.notifications.slack.enabled')) {
            return; // disabled in config
        }

        $title = ucfirst($event->type).' Dev Report ('.$event->end->format('Y-m-d').')';

        $this->notifier->send($event->markdown, $title);
    }
}
