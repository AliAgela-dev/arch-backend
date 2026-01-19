<?php

namespace App\Http\Requests\DocumentType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['sometimes', 'boolean'],
            'requirement_conditions' => ['nullable', 'array'],
            'requirement_conditions.operator' => ['required_with:requirement_conditions', 'in:AND,OR'],
            'requirement_conditions.conditions' => ['required_with:requirement_conditions', 'array'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
