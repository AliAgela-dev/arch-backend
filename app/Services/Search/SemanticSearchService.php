<?php

namespace App\Services\Search;

use App\Services\Embedding\VectorService;
use Illuminate\Support\Facades\DB;

class SemanticSearchService
{
    public function __construct(
        private readonly VectorService $vectorService,
    ) {}

    /**
     * Perform semantic search using pgvector cosine distance.
     *
     * @param  array<string, mixed>  $filters  Optional filters: faculty_id, program_id, student_status
     * @return array<int, \stdClass>
     */
    public function search(string $query, int $limit = 20, array $filters = []): array
    {
        $queryVector = $this->vectorService->generateForQuery($query);

        $vectorString = '['.implode(',', $queryVector).']';

        $whereClauses = [];
        $bindings = [
            'vector' => $vectorString,
            'vector_order' => $vectorString,
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
                1 - (de.vector <=> :vector) AS similarity_score
            FROM document_embeddings de
            JOIN document_contents dc ON dc.id = de.document_content_id
            JOIN student_documents sd ON sd.id = dc.student_document_id
            LEFT JOIN students s ON s.id = sd.student_id
            LEFT JOIN faculties f ON f.id = s.faculty_id
            LEFT JOIN programs p ON p.id = s.program_id
            WHERE 1=1 {$whereSQL}
            ORDER BY de.vector <=> :vector_order ASC
            LIMIT :limit
        ";

        return DB::select($sql, $bindings);
    }
}
