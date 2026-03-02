<?php

namespace App\Services\Embedding;

use App\Contracts\EmbeddingClientInterface;
use App\Models\DocumentContent;
use App\Models\DocumentEmbedding;

class VectorService
{
    public function __construct(
        private readonly EmbeddingClientInterface $client,
    ) {}

    /**
     * Generate and store an embedding for a document content page.
     *
     * Uses updateOrCreate for retry idempotency — running the same page
     * twice overwrites rather than duplicating.
     */
    public function generateForContent(DocumentContent $content): DocumentEmbedding
    {
        $vector = $this->client->embed($content->content);

        return DocumentEmbedding::updateOrCreate(
            ['document_content_id' => $content->id],
            ['vector' => $vector],
        );
    }

    /**
     * Generate an embedding for a search query (not stored).
     *
     * @return array<float> 768-dimensional vector
     */
    public function generateForQuery(string $query): array
    {
        return $this->client->embedQuery($query);
    }
}
