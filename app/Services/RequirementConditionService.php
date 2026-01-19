<?php

namespace App\Services;

use App\Models\Student;

class RequirementConditionService
{
    /**
     * Evaluate if a document type is required for a given student.
     *
     * @param array|null $conditions JSON conditions from DocumentType
     * @param Student $student The student to evaluate against
     * @return bool Whether the document is required
     */
    public function evaluate(?array $conditions, Student $student): bool
    {
        // No conditions means always required (if is_required is true)
        if ($conditions === null || empty($conditions)) {
            return true;
        }

        $operator = $conditions['operator'] ?? 'AND';
        $conditionsList = $conditions['conditions'] ?? [];

        if (empty($conditionsList)) {
            return true;
        }

        $results = [];

        foreach ($conditionsList as $condition) {
            $results[] = $this->evaluateSingleCondition($condition, $student);
        }

        return $operator === 'OR'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    /**
     * Evaluate a single condition against a student.
     */
    protected function evaluateSingleCondition(array $condition, Student $student): bool
    {
        $field = $condition['field'] ?? null;
        $op = $condition['op'] ?? '=';
        $value = $condition['value'] ?? null;

        if (!$field) {
            return true;
        }

        $studentValue = $this->getStudentValue($student, $field);

        return match ($op) {
            '=' => $studentValue == $value,
            '!=' => $studentValue != $value,
            'in' => is_array($value) && in_array($studentValue, $value),
            'not_in' => is_array($value) && !in_array($studentValue, $value),
            '>' => $studentValue > $value,
            '<' => $studentValue < $value,
            '>=' => $studentValue >= $value,
            '<=' => $studentValue <= $value,
            default => true,
        };
    }

    /**
     * Get a value from the student model.
     */
    protected function getStudentValue(Student $student, string $field): mixed
    {
        // Handle enum values
        if (in_array($field, ['student_status', 'location_status'])) {
            $value = $student->{$field};
            return $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $student->{$field} ?? null;
    }

    /**
     * Get required document types for a student.
     *
     * @param Student $student
     * @return \Illuminate\Support\Collection
     */
    public function getRequiredDocumentTypes(Student $student): \Illuminate\Support\Collection
    {
        return \App\Models\DocumentType::active()
            ->required()
            ->get()
            ->filter(fn ($docType) => $this->evaluate($docType->requirement_conditions, $student));
    }
}
