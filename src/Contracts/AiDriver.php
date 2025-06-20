<?php

namespace AIReporter\Contracts;

/**
 * Core contract every AI driver must implement.
 * Keeps the rest of the package vendor-agnostic (OpenAI, Azure, Vertex, etc.).
 */
interface AiDriver
{
    /**
     * Generate content from a prompt.
     *
     * @param  string  $prompt  Fully-formed user prompt including any template text.
     * @return string AI-generated Markdown (or plain text) report body.
     */
    public function generate(string $prompt): string;
}
