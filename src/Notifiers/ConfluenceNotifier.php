<?php

declare(strict_types=1);

namespace AIReporter\Notifiers;

use AIReporter\Contracts\Notifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends a report to Atlassian Confluence.
 *
 *  • Looks for an existing page with the given title in the target space.
 *  • If found → updates that page (increments version).
 *  • If not found → creates a new page under the configured parent.
 *
 * Authentication uses basic auth with email + API token.
 */
final class ConfluenceNotifier implements Notifier
{
    private HttpClientInterface $http;

    /** @param string[] $labels */
    public function __construct(
        string $baseUrl,
        string $email,
        string $apiToken,
        private string $spaceKey,
        private string $parentPageId,
        private array $labels = [],
        ?HttpClientInterface $http = null,
        private ?LoggerInterface $logger = null,
    ) {
        $this->http = $http ?? HttpClient::create([
            'base_uri' => rtrim($baseUrl, '/').'/wiki/rest/api/',
            'auth_basic' => [$email, $apiToken],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Public API */
    /* ------------------------------------------------------------------ */

    public function send(string $message, string $title = 'AI Report'): void
    {
        try {
            $pageId = $this->findPageIdByTitle($title);

            if ($pageId) {
                $this->updatePage($pageId, $title, $message);
            } else {
                $this->createPage($title, $message);
            }
        } catch (\Throwable $e) {
            $this->logger?->error('[ConfluenceNotifier] '.$e->getMessage());
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Internal helpers */
    /* ------------------------------------------------------------------ */

    private function findPageIdByTitle(string $title): ?string
    {
        $resp = $this->http->request('GET', 'content', [
            'query' => [
                'title' => $title,
                'spaceKey' => $this->spaceKey,
                'type' => 'page',
            ],
        ]);

        $data = $resp->toArray(false);

        return ($data['size'] ?? 0) > 0 ? ($data['results'][0]['id'] ?? null) : null;
    }

    private function createPage(string $title, string $markdown): void
    {
        $this->http->request('POST', 'content', [
            'json' => [
                'type' => 'page',
                'title' => $title,
                'ancestors' => [['id' => $this->parentPageId]],
                'space' => ['key' => $this->spaceKey],
                'body' => [
                    'storage' => [
                        'value' => $this->markdownToStorage($markdown),
                        'representation' => 'storage',
                    ],
                ],
                'metadata' => $this->labels ? ['labels' => array_map(
                    fn (string $l) => ['prefix' => 'global', 'name' => $l],
                    $this->labels
                )] : new \stdClass,
            ],
        ]);
    }

    private function updatePage(string $pageId, string $title, string $markdown): void
    {
        // Get current version number first
        $current = $this->http->request('GET', "content/{$pageId}", [
            'query' => ['expand' => 'version'],
        ])->toArray();

        $nextVersion = ($current['version']['number'] ?? 1) + 1;

        $this->http->request('PUT', "content/{$pageId}", [
            'json' => [
                'id' => $pageId,
                'type' => 'page',
                'title' => $title,
                'version' => ['number' => $nextVersion],
                'body' => [
                    'storage' => [
                        'value' => $this->markdownToStorage($markdown),
                        'representation' => 'storage',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Confluence storage format supports Markdown via {@code markdown} macro
     * or straight XHTML.  Easiest approach is wrap Markdown in a macro.
     */
    private function markdownToStorage(string $md): string
    {
        return '<ac:structured-macro ac:name="markdown">'
             .'<ac:plain-text-body><![CDATA['.$md.']]></ac:plain-text-body>'
             .'</ac:structured-macro>';
    }
}
