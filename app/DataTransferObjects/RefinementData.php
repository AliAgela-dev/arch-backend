<?php

namespace App\DataTransferObjects;

class RefinementData
{
    public function __construct(
        public ?string $studentNumber = null,
        public ?string $studentName = null,
        public ?string $college = null,
        public ?string $program = null,
        public ?string $documentType = null,
        public ?string $enrollmentDate = null,
        public float $confidence = 0.0,
        public array $additionalFields = [],
    ) {}

    /**
     * Create a DTO from the parsed Gemini JSON response.
     * Maps snake_case keys to camelCase properties.
     * Multiplies confidence from 0-1 to 0-100.
     */
    public static function fromArray(array $data): self
    {
        $knownKeys = [
            'student_number', 'student_name', 'college', 'program',
            'document_type', 'enrollment_date', 'confidence', 'additional_fields',
        ];

        $additional = $data['additional_fields'] ?? [];

        // Collect any unknown keys into additional_fields
        foreach ($data as $key => $value) {
            if (! in_array($key, $knownKeys, true) && $value !== null) {
                $additional[$key] = $value;
            }
        }

        $rawConfidence = (float) ($data['confidence'] ?? 0);
        $confidence = $rawConfidence <= 1.0 ? $rawConfidence * 100 : $rawConfidence;

        return new self(
            studentNumber: $data['student_number'] ?? null,
            studentName: $data['student_name'] ?? null,
            college: $data['college'] ?? null,
            program: $data['program'] ?? null,
            documentType: $data['document_type'] ?? null,
            enrollmentDate: $data['enrollment_date'] ?? null,
            confidence: $confidence,
            additionalFields: $additional,
        );
    }

    /**
     * Serialize back to snake_case array for DB storage.
     */
    public function toArray(): array
    {
        return [
            'student_number' => $this->studentNumber,
            'student_name' => $this->studentName,
            'college' => $this->college,
            'program' => $this->program,
            'document_type' => $this->documentType,
            'enrollment_date' => $this->enrollmentDate,
            'confidence' => $this->confidence,
            'additional_fields' => $this->additionalFields,
        ];
    }

    /**
     * Check if the confidence score meets the threshold for auto-classification.
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence >= config('ai.pipeline.confidence_threshold', 85);
    }

    /**
     * Check if the minimum required fields are present for auto-classification.
     */
    public function isComplete(): bool
    {
        return $this->studentNumber !== null
            && $this->studentName !== null
            && $this->college !== null;
    }

    /**
     * Gemini responseJsonSchema definition matching this DTO's structure.
     *
     * Available for future use with Gemini's structured output feature
     * (generationConfig.responseJsonSchema). Not currently wired into
     * the client — prompt + responseMimeType is sufficient for now.
     *
     * The schema lives here because the DTO is the source of truth for
     * the response structure. When DTO fields change, the schema changes with it.
     */
    public static function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'student_number' => [
                    'type' => 'string',
                    'description' => 'Student university number/ID (الرقم الدراسي)',
                    'nullable' => true,
                ],
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Student full name, preferring Arabic (الاسم الكامل)',
                    'nullable' => true,
                ],
                'college' => [
                    'type' => 'string',
                    'description' => 'College/faculty name (الكلية)',
                    'nullable' => true,
                ],
                'program' => [
                    'type' => 'string',
                    'description' => 'Academic program/major name (البرنامج)',
                    'nullable' => true,
                ],
                'document_type' => [
                    'type' => 'string',
                    'description' => 'Document type: Passport, National ID, High School Certificate, International High School Certificate, Admission Letter, Enrollment Letter, Birth Certificate, Personal Photo, Equivalency Letter, Medical Certificate',
                    'nullable' => true,
                ],
                'enrollment_date' => [
                    'type' => 'string',
                    'description' => 'Enrollment/admission date in YYYY-MM-DD format',
                    'nullable' => true,
                ],
                'confidence' => [
                    'type' => 'number',
                    'description' => 'Extraction confidence from 0.0 to 1.0',
                ],
                'additional_fields' => [
                    'type' => 'object',
                    'description' => 'Any other relevant extracted information',
                ],
            ],
            'required' => ['confidence', 'additional_fields'],
        ];
    }
}
