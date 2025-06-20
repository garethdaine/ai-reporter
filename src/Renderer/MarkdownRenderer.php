<?php

namespace AIReporter\Renderer;

/**
 * Minimal post-processing pass to ensure generated Markdown meets house rules.
 * Extend later with link rewriting, linting, table-of-contents, etc.
 */
final class MarkdownRenderer
{
    /**
     * Normalise line endings, trim leading/trailing whitespace, etc.
     */
    public function clean(string $markdown): string
    {
        // Convert Windows line endings to LF, trim outer blank lines.
        $md = preg_replace('/\r\n?/', "\n", $markdown ?? '');

        return trim($md);
    }
}
