<?php

namespace AIReporter\Laravel\Console;

use AIReporter\Services\ReportGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateReport extends Command
{
    protected $signature = 'report:generate
                            {--type=weekly : weekly|monthly}
                            {--start= : YYYY-MM-DD}
                            {--end=   : YYYY-MM-DD}
                            {--dry    : Output to console only, no persistence}';

    protected $description = 'Generate an AI Reporter dev progress report.';

    public function __construct(private ReportGenerator $reports)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type') ?: 'weekly';
        $end = CarbonImmutable::parse($this->option('end') ?: now());
        $start = CarbonImmutable::parse(
            $this->option('start')
            ?: ($type === 'weekly' ? $end->subWeek() : $end->startOfMonth())
        );

        [$path, $markdown] = $this->reports->run($type, $start, $end);

        if ($this->option('dry')) {
            $this->info($markdown);

            return self::SUCCESS;
        }

        $this->line("Report stored at: $path");

        return self::SUCCESS;
    }
}
