<?php

namespace AIReporter\Support;

use Symfony\Component\Process\Process;

final class CliTools
{
    public static function exists(string $command): bool
    {
        // cross-platform: `command -v` (posix) or `where` (windows)
        $check = new Process(PHP_OS_FAMILY === 'Windows'
            ? ['where', $command]
            : ['command', '-v', $command]
        );

        $check->run();

        return $check->isSuccessful();
    }
}
