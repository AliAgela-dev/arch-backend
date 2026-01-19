<?php

namespace Database\Factories;

use App\Enums\FileStatus;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentDocumentFactory extends Factory
{
    protected $model = StudentDocument::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'document_type_id' => DocumentType::factory(),
            'file_number' => 'DOC-' . now()->format('Ymd') . '-' . strtoupper($this->faker->bothify('????????')),
            'file_status' => FileStatus::DRAFT,
            'notes' => $this->faker->optional()->sentence(),
            'submitted_at' => null,
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_status' => FileStatus::COMPLETE,
            'submitted_at' => now(),
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_status' => FileStatus::INCOMPLETE,
        ]);
    }
}
