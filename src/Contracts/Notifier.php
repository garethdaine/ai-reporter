<?php

namespace AIReporter\Contracts;

interface Notifier
{
    public function send(string $message, string $title = 'AI Report'): void;
}
