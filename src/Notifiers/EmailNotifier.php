<?php

declare(strict_types=1);

namespace AIReporter\Notifiers;

use AIReporter\Contracts\Notifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailNotifier implements Notifier
{
    public function __construct(
        private MailerInterface $mailer,
        private string $from,
        private array $recipients,
        private ?LoggerInterface $logger = null
    ) {}

    public function send(string $message, string $title = 'New AI Report'): void
    {
        $email = (new Email)
            ->from($this->from)
            ->to(...$this->recipients)
            ->subject($title)
            ->text($message);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger?->error('[EmailNotifier] Failed to send email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
