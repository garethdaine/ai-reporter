<?php

declare(strict_types=1);

namespace AIReporter\Drivers;

use AIReporter\Contracts\AiDriver;

/**
 * Offline / dry-run driver that returns a canned response.
 *
 * You can:
 *  • Inject a custom placeholder via constructor
 *  • Rely on the default minimal body
 */
final readonly class NullDriver implements AiDriver
{
    public function __construct(
        private string $placeholder = '_(AI disabled — placeholder report)_'
    ) {}

    public function generate(string $prompt): string
    {
        // Option A – just return the placeholder
        return $this->placeholder;

        /*  Option B – return the original prompt for debugging
            return "```\n{$prompt}\n```";
        */
    }
}
