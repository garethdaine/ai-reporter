<?php

// src/Cli/GenerateReportCommand.php

declare(strict_types=1);

namespace AIReporter\Cli;

use AIReporter\Services\ReportGenerator;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateReportCommand extends Command
{
    protected static $defaultName = 'report:generate';

    public function __construct(private ReportGenerator $reports)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate an AI Reporter dev progress report')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'weekly|monthly', 'weekly')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Output only; do not save');
    }

    protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $type = $in->getOption('type');
        $end = new DateTimeImmutable($in->getOption('end') ?: 'now');
        $start = new DateTimeImmutable(
            $in->getOption('start')
            ?: ($type === 'weekly' ? $end->modify('-7 days') : $end->modify('first day of this month'))
        );

        [$path, $markdown] = $this->reports->run($type, $start, $end);

        if ($in->getOption('dry')) {
            $out->writeln($markdown);
        } else {
            $out->writeln("<info>Report stored at:</info> {$path}");
        }

        return Command::SUCCESS;
    }
}
