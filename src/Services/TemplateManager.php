<?php

namespace AIReporter\Services;

use DateTimeImmutable;

final class TemplateManager
{
    public function __construct(
        private readonly string $templatePath,    // dir for user-overrides
        private readonly string $reportStore      // dir to write finished md
    ) {}

    /* ---------------------------------------------------------------------
     |  Access & prompt helpers
     | -------------------------------------------------------------------*/

    public function getStub(string $type): string
    {
        $local = "{$this->templatePath}/{$type}.md.stub";
        $pkg = __DIR__."/../../Templates/{$type}.md.stub";

        return file_exists($local) ? file_get_contents($local)
                                   : file_get_contents($pkg);
    }

    /**
     * Build the prompt: stub + placeholder replacement + context data.
     */
    public function buildPrompt(
        string $type,
        string $commitLog,
        string $tree,
        ?string $previous,
        string $period
    ): string {
        $stub = $this->getStub($type);

        $search = ['{{commit_log}}', '{{tree}}', '{{previous_report}}', '{{period}}'];
        $replace = [$commitLog, $tree, $previous ?? '*None*', $period];

        return str_replace($search, $replace, $stub);
    }

    /* ----------------------------------------------------------------------
     |  Persistence helpers
     | --------------------------------------------------------------------*/

    /**
     * Persist markdown and return absolute path.
     */
    public function save(
        string $type,
        DateTimeImmutable $endDate,
        string $markdown
    ): string {
        $dir = "{$this->reportStore}/{$type}";
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = "{$dir}/{$endDate->format('Y-m-d')}.md";
        file_put_contents($file, $markdown);

        return realpath($file);
    }

    /**
     * Return the most recent report before the given date, or null.
     */
    public function latestReport(string $type, DateTimeImmutable $before): ?string
    {
        $dir = "{$this->reportStore}/{$type}";
        if (! is_dir($dir)) {
            return null;
        }

        $files = array_filter(
            glob("{$dir}/*.md") ?: [],
            fn ($f) => filemtime($f) < $before->getTimestamp()
        );

        if ($files === []) {
            return null;
        }

        rsort($files);            // newest first

        return file_get_contents($files[0]);
    }
}
