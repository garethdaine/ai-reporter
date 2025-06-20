<?php

declare(strict_types=1);

use AIReporter\Contracts\AiDriver;
use AIReporter\Contracts\CommitProvider;
use AIReporter\Renderer\MarkdownRenderer;
use AIReporter\Services\ReportGenerator;
use AIReporter\Services\TemplateManager;

it('generates a report using stubs and AI driver', function () {
    $ai = Mockery::mock(AiDriver::class);
    $commits = Mockery::mock(CommitProvider::class);
    $renderer = new MarkdownRenderer;

    $type = 'weekly';
    $start = new DateTimeImmutable('-7 days');
    $end = new DateTimeImmutable;
    $fakeTree = '/project/src/...';
    $fakeResponse = '## Clean Report';

    $stubDir = __DIR__.'/../../stubs';
    $reportDir = __DIR__.'/../../tmp-reports';

    if (! is_dir($stubDir)) {
        mkdir($stubDir, recursive: true);
    }
    file_put_contents($stubDir.'/weekly.md.stub', 'Weekly stub');

    $templates = new TemplateManager($stubDir, $reportDir);

    $commitLog = "Fix bug\nAdd feature";

    $commits->shouldReceive('between')
        ->with($start, $end)
        ->once()
        ->andReturn($commitLog);

    $commits->shouldReceive('treeSnapshot')->with(3)->andReturn($fakeTree);
    $ai->shouldReceive('generate')->andReturn($fakeResponse);

    $generator = new ReportGenerator($ai, $templates, $commits, $renderer);
    $result = $generator->run($type, $start, $end);

    expect($result[1])->toBe($fakeResponse);
});
