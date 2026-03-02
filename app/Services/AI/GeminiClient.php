<?php

namespace App\Services\AI;

use App\Contracts\AiClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GeminiClient implements AiClientInterface
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    private int $rateLimit;

    private float $temperature;

    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->baseUrl = config('ai.gemini.base_url');
        $this->model = config('ai.gemini.model');
        $this->rateLimit = config('ai.gemini.rate_limit');
        $this->temperature = config('ai.gemini.temperature');
        $this->timeout = config('ai.gemini.timeout');
    }

    /**
     * {@inheritDoc}
     */
    public function generateContent(string $prompt, ?string $systemInstruction = null): array
    {
        $this->enforceRateLimit();

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => $this->temperature,
            ],
        ];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout($this->timeout)->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Gemini API request failed [{$response->status()}]: {$response->body()}"
            );
        }

        return $response->json();
    }

    private function enforceRateLimit(): void
    {
        $executed = RateLimiter::attempt(
            'gemini-api',
            $this->rateLimit,
            fn () => true,
            60
        );

        if (! $executed) {
            throw new RuntimeException('Gemini API rate limit exceeded. Will retry.');
        }
    }
}
