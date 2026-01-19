<?php

namespace App\Http\Requests\Borrowing;

use App\Enums\UserRole;
use App\Models\StudentDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBorrowingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only faculty_staff can request borrowing
        return $this->user() && $this->user()->hasRole(UserRole::faculty_staff->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_document_id' => [
                'required',
                'uuid',
                Rule::exists('student_documents', 'id'),
                function ($attribute, $value, $fail) {
                    // Check if document exists and is not already borrowed
                    $document = StudentDocument::find($value);
                    
                    if (!$document) {
                        $fail('The selected student document does not exist.');
                        return;
                    }
                    
                    // Check if document is currently borrowed
                    if ($document->isBorrowed()) {
                        $fail('This document is currently borrowed and unavailable.');
                        return;
                    }
                },
            ],
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_document_id.required' => 'Please select a student document to borrow.',
            'student_document_id.uuid' => 'Invalid document ID format.',
            'student_document_id.exists' => 'The selected document does not exist.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }
}
