<?php

namespace AIReporter\Services;

use AIReporter\Contracts\AiDriver;
use AIReporter\Contracts\CommitProvider;
use AIReporter\Events\ReportGenerated;
use AIReporter\Renderer\MarkdownRenderer;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class ReportGenerator
{
    public function __construct(
        private AiDriver $ai,
        private TemplateManager $templates,
        private CommitProvider $commits,
        private MarkdownRenderer $renderer,
        private ?EventDispatcherInterface $events = null   // nullable for agnostic use
    ) {}

    /**
     * Generate → save → (optionally) dispatch event → return tuple.
     *
     * @return array{path:string, markdown:string}
     */
    public function run(
        string $type,                     // weekly | monthly
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        // 1. Gather context
        $commitLog = $this->commits->between($start, $end);
        $tree = $this->commits->treeSnapshot(3);
        $previous = $this->templates->latestReport($type, $start);

        // 2. Build prompt & call AI
        $prompt = $this->templates->buildPrompt(
            $type, $commitLog, $tree, $previous,
            "{$start->format('Y-m-d')} → {$end->format('Y-m-d')}"
        );

        $markdown = $this->renderer->clean(
            $this->ai->generate($prompt)
        );

        // 3. Persist
        $path = $this->templates->save($type, $end, $markdown);

        // 4. Fire domain event (if dispatcher provided)
        $this->events?->dispatch(
            new ReportGenerated($type, $start, $end, $path, $markdown)
        );

        return [$path, $markdown];
    }
}
