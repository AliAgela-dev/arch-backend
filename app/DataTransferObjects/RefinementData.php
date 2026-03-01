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
        return $this->confidence >= config('services.pipeline.confidence_threshold', 85);
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
}
