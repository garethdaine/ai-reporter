<?php

namespace AIReporter\Services\CommitProviders;

use AIReporter\Contracts\CommitProvider;
use AIReporter\Support\CliTools;
use AIReporter\Support\DirectoryTree;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class GitCommitProvider implements CommitProvider
{
    /**
     * @var string Root path of the git repository.
     */
    private readonly string $repoPath;

    /**
     * Root of the repository (defaults to project base path).
     */
    public function __construct(string $repoPath = '')
    {
        $this->repoPath = $repoPath ?: \dirname(__DIR__, 4);

        if (! CliTools::exists('git')) {
            throw new \RuntimeException('Git CLI is required for AI Reporter.');
        }
    }

    /** {@inheritdoc} */
    public function between(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): string {
        $format = '- %h %s (%an)';
        $process = new Process([
            'git', '-C', $this->repoPath,
            'log',
            "--pretty=format:$format",
            '--no-merges',
            '--reverse',
            "--since={$start->format('Y-m-d')}",
            "--until={$end->format('Y-m-d 23:59:59')}",
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput()) ?: '*No commits in period*';
    }

    /** {@inheritdoc} */
    public function treeSnapshot(int $maxDepth = 3): string
    {
        // Prefer the real `tree` command if available.
        if (CliTools::exists('tree')) {
            return (new Process([
                'tree', $this->repoPath,
                '-L', (string) $maxDepth,
                '-I', 'vendor|node_modules|storage|logs',
            ]))
                ->mustRun()
                ->getOutput();
        }

        return DirectoryTree::generate($this->repoPath, $maxDepth);
    }

    /**
     * Lightweight fallback if `tree` binary is absent.
     */
    private function phpTree(string $dir, int $depth, int $level = 0): string
    {
        if ($depth === 0) {
            return '';
        }

        $out = '';
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (\in_array($item, ['vendor', 'node_modules', 'storage', 'logs'], true)) {
                continue;
            }

            $prefix = str_repeat('│   ', max(0, $level - 1)).
                      ($level ? '├── ' : '');
            $out .= $prefix.$item.PHP_EOL;

            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $out .= $this->phpTree($path, $depth - 1, $level + 1);
            }
        }

        return rtrim($out);
    }
}
