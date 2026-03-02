<?php

namespace App\Services\Embedding;

use App\Contracts\EmbeddingClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GeminiEmbeddingClient implements EmbeddingClientInterface
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    private int $rateLimit;

    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.vertex.api_key');
        $this->baseUrl = config('ai.vertex.base_url');
        $this->model = config('ai.vertex.embedding_model');
        $this->rateLimit = config('ai.vertex.rate_limit');
        $this->timeout = config('ai.vertex.timeout');
    }

    /**
     * {@inheritDoc}
     */
    public function embed(string $text): array
    {
        return $this->callEmbedApi($text, 'RETRIEVAL_DOCUMENT');
    }

    /**
     * {@inheritDoc}
     */
    public function embedQuery(string $query): array
    {
        return $this->callEmbedApi($query, 'RETRIEVAL_QUERY');
    }

    /**
     * @return array<float>
     */
    private function callEmbedApi(string $text, string $taskType): array
    {
        $this->enforceRateLimit();

        $text = mb_substr($text, 0, 8000);

        $payload = [
            'contents' => [
                ['parts' => [['text' => $text]]],
            ],
            'embedding_config' => [
                'task_type' => $taskType,
            ],
        ];

        $url = "{$this->baseUrl}/models/{$this->model}:embedContent?key={$this->apiKey}";

        $response = Http::timeout($this->timeout)->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Gemini Embedding API request failed [{$response->status()}]: {$response->body()}"
            );
        }

        $values = $response->json('embeddings.0.values');

        if (! is_array($values) || count($values) !== 768) {
            throw new RuntimeException(
                'Gemini Embedding API returned invalid vector: expected 768 dimensions, got '
                .(is_array($values) ? count($values) : 'null')
            );
        }

        return $values;
    }

    private function enforceRateLimit(): void
    {
        $executed = RateLimiter::attempt(
            'vertex-embedding-api',
            $this->rateLimit,
            fn () => true,
            60
        );

        if (! $executed) {
            throw new RuntimeException('Vertex Embedding API rate limit exceeded. Will retry.');
        }
    }
}
