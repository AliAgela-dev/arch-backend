<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;
use Throwable;

class HybridSearchService
{
    public function __construct(
        private readonly SemanticSearchService $semanticSearch,
    ) {}

    /**
     * Search with semantic (pgvector) primary and keyword (tsvector) fallback.
     *
     * @param  array<string, mixed>  $filters  Optional filters: faculty_id, program_id, student_status
     * @return array{mode: string, results: array, fallback_reason: string|null}
     */
    public function search(string $query, int $limit = 20, array $filters = []): array
    {
        try {
            $results = $this->semanticSearch->search($query, $limit, $filters);

            return [
                'mode' => 'semantic',
                'results' => $results,
                'fallback_reason' => null,
            ];
        } catch (Throwable $e) {
            $results = $this->keywordSearch($query, $limit, $filters);

            return [
                'mode' => 'keyword',
                'results' => $results,
                'fallback_reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fallback keyword search using PostgreSQL tsvector full-text search.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, \stdClass>
     */
    protected function keywordSearch(string $query, int $limit, array $filters): array
    {
        $whereClauses = [];
        $bindings = [
            'query_rank' => $query,
            'query_where' => $query,
            'limit' => $limit,
        ];

        if (! empty($filters['faculty_id'])) {
            $whereClauses[] = 's.faculty_id = :faculty_id';
            $bindings['faculty_id'] = $filters['faculty_id'];
        }

        if (! empty($filters['program_id'])) {
            $whereClauses[] = 's.program_id = :program_id';
            $bindings['program_id'] = $filters['program_id'];
        }

        if (! empty($filters['student_status'])) {
            $whereClauses[] = 's.student_status = :student_status';
            $bindings['student_status'] = $filters['student_status'];
        }

        $whereSQL = count($whereClauses) > 0
            ? 'AND '.implode(' AND ', $whereClauses)
            : '';

        $sql = "
            SELECT
                dc.id AS content_id,
                dc.student_document_id,
                dc.content,
                dc.page_number,
                sd.file_number,
                s.id AS student_id,
                s.name AS student_name,
                s.student_number,
                f.name_ar AS faculty_name,
                p.name_ar AS program_name,
                ts_rank(dc.search_vector, plainto_tsquery('simple', :query_rank)) AS similarity_score
            FROM document_contents dc
            JOIN student_documents sd ON sd.id = dc.student_document_id
            LEFT JOIN students s ON s.id = sd.student_id
            LEFT JOIN faculties f ON f.id = s.faculty_id
            LEFT JOIN programs p ON p.id = s.program_id
            WHERE dc.search_vector @@ plainto_tsquery('simple', :query_where)
            {$whereSQL}
            ORDER BY similarity_score DESC
            LIMIT :limit
        ";

        return DB::select($sql, $bindings);
    }
}
