<?php

namespace App\Http\Requests\Student;

use App\Enums\LocationStatus;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student') ?? $this->route('id');

        return [
            'student_number' => ['sometimes', 'string', 'max:50', Rule::unique('students', 'student_number')->ignore($studentId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'nationality' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'faculty_id' => ['sometimes', 'integer', 'exists:faculties,id'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'drawer_id' => ['nullable', 'uuid', 'exists:drawers,id'],
            'enrollment_year' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'student_status' => ['sometimes', Rule::enum(StudentStatus::class)],
            'location_status' => ['sometimes', Rule::enum(LocationStatus::class)],
        ];
    }
}
