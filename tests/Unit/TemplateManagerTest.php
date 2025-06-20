<?php

declare(strict_types=1);

use AIReporter\Services\TemplateManager;

beforeEach(function () {
    $this->templatePath = __DIR__.'/../../stubs';
    $this->reportStore = __DIR__.'/../../tmp-reports';

    if (! is_dir($this->reportStore)) {
        mkdir($this->reportStore, recursive: true);
    }

    $this->manager = new TemplateManager(
        templatePath: $this->templatePath,
        reportStore: $this->reportStore
    );
});

it('loads stub from custom path if present', function () {
    $type = 'weekly';
    $stubFile = "{$this->templatePath}/{$type}.md.stub";

    file_put_contents($stubFile, 'Custom Weekly Template');

    $stub = $this->manager->getStub($type);

    expect($stub)->toContain('Custom Weekly Template');

    unlink($stubFile);
});

it('falls back to internal stub if custom missing', function () {
    $type = 'monthly';

    $stub = $this->manager->getStub($type);

    expect($stub)->toContain('📅 **Monthly Dev Summary');
});

it('creates a report file and returns the path', function () {
    $markdown = "# Report\n\nDetails here.";
    $path = $this->manager->save('weekly', new DateTimeImmutable, $markdown);

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe($markdown);

    unlink($path);
});
