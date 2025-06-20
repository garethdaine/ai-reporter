<?php

declare(strict_types=1);

use AIReporter\Renderer\MarkdownRenderer;

/**
 * MarkdownRenderer::clean()
 *
 * Should:
 *  - Convert all CRLF or CR line endings to LF
 *  - Trim leading and trailing blank lines
 *  - Preserve legitimate blank lines inside the body
 */
it('normalises line endings and trims outer whitespace', function () {
    $raw = "\r\n\r\n# Title\r\n\r\nParagraph text.  \r\n\r\n";
    $renderer = new MarkdownRenderer;

    $clean = $renderer->clean($raw);

    expect($clean)->toBe("# Title\n\nParagraph text.");
});

it('is idempotent (cleaning twice yields same result)', function () {
    $renderer = new MarkdownRenderer;
    $markdown = "## Heading\n\nBody";

    expect($renderer->clean($renderer->clean($markdown)))
        ->toBe($markdown);
});

it('preserves internal blank lines and markdown structure', function () {
    $renderer = new MarkdownRenderer;

    $raw = <<<'MD'
## Heading

- Item 1

- Item 2

```php
echo "code block";
```
MD;

    $expected = <<<'MD'
## Heading

- Item 1

- Item 2

```php
echo "code block";
```
MD;

    expect($renderer->clean($raw))->toBe($expected);
});

it('returns empty string when given empty input', function () {
    $renderer = new MarkdownRenderer;
    expect($renderer->clean(''))->toBe('');
});
