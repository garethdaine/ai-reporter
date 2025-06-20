<?php

namespace AIReporter\Composer;

use AIReporter\Support\CliTools;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

final class CheckTools
{
    public static function handle(Event $event): void
    {
        $io = $event->getIO();

        $missing = [];

        foreach (['git', 'tree'] as $cmd) {
            if (! CliTools::exists($cmd)) {
                $missing[] = $cmd;
            }
        }

        if ($missing === []) {
            $io->write('<info>[Reporter]</info> All CLI prerequisites found.');

            return;
        }

        // If git is missing, hard-fail
        if (in_array('git', $missing, true)) {
            $io->writeError('<error>[Reporter]</error> Git is required but not found.');
            exit(1);
        }

        // tree is optional: warn + document install commands
        if (in_array('tree', $missing, true)) {
            $io->write('<warning>[Reporter]</warning> `tree` not found; falling back to slower PHP scan.');
            $io->write('You can install it for best results:');
            $io->write('  • macOS:  brew install tree');
            $io->write('  • Ubuntu: sudo apt-get install tree');
            $io->write('  • Fedora: sudo dnf install tree');
            $io->write('  • Windows (Admin PowerShell): choco install tree');
        }

        $auto = in_array('--with-tree', $_SERVER['argv'], true);

        if ($auto && in_array('tree', $missing, true)) {
            $cmd = match (PHP_OS_FAMILY) {
                'Darwin' => ['brew', 'install', 'tree'],
                'Linux' => file_exists('/etc/debian_version')
                            ? ['apt-get', 'install', '-y', 'tree']
                            : ['dnf', 'install', '-y', 'tree'],
                default => []
            };

            if ($cmd) {
                (new Process($cmd))->setTty(false)->run();
                // re-check …
            }
        }
    }
}
