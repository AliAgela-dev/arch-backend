<?php

namespace App\Contracts;

interface EmbeddingClientInterface
{
    /**
     * Embed document text for indexing (RETRIEVAL_DOCUMENT task type).
     *
     * @param string $text The text to embed
     * @return array<float> 768-dimensional float array
     * @throws \RuntimeException If the API call fails
     */
    public function embed(string $text): array;

    /**
     * Embed a search query (RETRIEVAL_QUERY task type).
     *
     * @param string $query The search query to embed
     * @return array<float> 768-dimensional float array
     * @throws \RuntimeException If the API call fails
     */
    public function embedQuery(string $query): array;
}
