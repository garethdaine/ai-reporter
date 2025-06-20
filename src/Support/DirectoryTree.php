<?php

declare(strict_types=1);

namespace AIReporter\Support;

final class DirectoryTree
{
    /**
     * Generate a tree-style directory snapshot.
     *
     * @param  string  $root  Root directory to scan.
     * @param  int  $maxDepth  How deep to traverse.
     * @param  array  $config  Options: exclude[], ascii, showCounts
     */
    public static function generate(string $root, int $maxDepth = 3, array $config = []): string
    {
        $options = array_merge([
            'exclude' => ['vendor', 'node_modules', 'storage', 'logs'],
            'ascii' => ! self::supportsUnicode(),
            'showCounts' => true,
        ], $config);

        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $output = ".\n";
        $output .= self::walk($root, 1, $maxDepth, '', $options);

        return $output;
    }

    private static function walk(string $path, int $depth, int $maxDepth, string $prefix, array $options): string
    {
        if ($depth > $maxDepth) {
            return '';
        }

        $items = array_filter(scandir($path) ?: [], fn ($i) => $i !== '.' && $i !== '..');
        usort($items, fn ($a, $b) => strnatcasecmp($a, $b));

        $count = count($items);
        $i = 0;
        $out = '';

        foreach ($items as $item) {
            if (in_array($item, $options['exclude'], true)) {
                continue;
            }

            $i++;
            $fullPath = $path.DIRECTORY_SEPARATOR.$item;
            $isDir = is_dir($fullPath);

            // Style options
            $lineChar = $options['ascii']
                ? ($i === $count ? '`--' : '|--')
                : ($i === $count ? '└──' : '├──');

            $out .= "{$prefix}{$lineChar} {$item}";

            if ($isDir && $options['showCounts']) {
                $summary = self::summarise($fullPath);
                $out .= " {$summary}";
            }

            $out .= "\n";

            if ($isDir) {
                $nextPrefix = $prefix.($options['ascii']
                    ? ($i === $count ? '    ' : '|   ')
                    : ($i === $count ? '    ' : '│   '));

                $out .= self::walk($fullPath, $depth + 1, $maxDepth, $nextPrefix, $options);
            }
        }

        return $out;
    }

    private static function summarise(string $path): string
    {
        $files = $dirs = 0;
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            is_dir("$path/$item") ? $dirs++ : $files++;
        }

        return "({$dirs} dirs, {$files} files)";
    }

    private static function supportsUnicode(): bool
    {
        // Dumb fallback: you could sniff for OS/terminal if needed
        return strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
    }
}
