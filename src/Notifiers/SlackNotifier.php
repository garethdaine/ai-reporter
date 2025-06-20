<?php

declare(strict_types=1);

namespace AIReporter\Notifiers;

use AIReporter\Contracts\Notifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SlackNotifier implements Notifier
{
    private HttpClientInterface $http;

    private ?LoggerInterface $logger;

    public function __construct(
        private string $webhookUrl,
        private string $channel = '',
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
        $this->http = HttpClient::create();
    }

    public function send(string $message, string $title = 'New AI Report'): void
    {
        $payload = [
            'text' => "*{$title}*\n\n".$message,
        ];

        if ($this->channel) {
            $payload['channel'] = $this->channel;
        }

        try {
            $this->http->request('POST', $this->webhookUrl, [
                'json' => $payload,
            ]);
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('[SlackNotifier] Failed to post to Slack', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
