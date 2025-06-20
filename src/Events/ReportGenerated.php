<?php

declare(strict_types=1);

namespace AIReporter\Events;

use DateTimeImmutable;

/**
 * Domain event fired after a report is generated and saved.
 *
 * This is framework-neutral. Any event dispatcher (Laravel, Symfony, custom)
 * can listen to this and trigger follow-up actions like:
 *  - Posting to Slack
 *  - Publishing to Confluence
 *  - Emailing a team summary
 */
final readonly class ReportGenerated
{
    public function __construct(
        public string $type,                           // "weekly", "monthly", etc.
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public string $storedPath,                     // absolute path to saved .md file
        public string $markdown                        // raw content of the report
    ) {}
}
