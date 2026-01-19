<?php

namespace App\Http\Requests\StudentDocument;

use App\Enums\FileStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'document_type_id' => ['required', 'uuid', 'exists:document_types,id'],
            'file_status' => ['required', Rule::enum(FileStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submitted_at' => ['nullable', 'date'],
            'temp_upload_id' => ['nullable', 'uuid', 'exists:temp_uploads,id'],
        ];
    }
}
