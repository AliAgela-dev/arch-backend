<?php

namespace App\Http\Requests\Student;

use App\Enums\LocationStatus;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
            'name' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'drawer_id' => ['nullable', 'uuid', 'exists:drawers,id'],
            'enrollment_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:enrollment_year'],
            'student_status' => ['required', Rule::enum(StudentStatus::class)],
            'location_status' => ['required', Rule::enum(LocationStatus::class)],
        ];
    }
}
