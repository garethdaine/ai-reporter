<?php

namespace AIReporter\Drivers;

use AIReporter\Contracts\AiDriver;
use OpenAI\Client as OpenAIClient;  // part of openai-php/laravel (or generic)

final readonly class OpenAiDriver implements AiDriver
{
    private OpenAIClient $client;

    public function __construct(
        string $apiKey,
        private string $model = 'gpt-4o-mini',
        private float $temperature = 0.2,
    ) {
        $this->client = \OpenAI::factory()->withApiKey($apiKey)->make();
    }

    public function generate(string $prompt): string
    {
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'temperature' => $this->temperature,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that generates concise, non-technical dev reports.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ]);

        return $response->choices[0]?->message?->content ?? '';
    }
}
