<?php

namespace App\Http\Requests\StudentDocument;

use App\Enums\FileStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $documentId = $this->route('student_document') ?? $this->route('id');

        return [
            'file_status' => ['sometimes', Rule::enum(FileStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submitted_at' => ['nullable', 'date'],
            'temp_upload_id' => ['nullable', 'uuid', 'exists:temp_uploads,id'],
        ];
    }
}
