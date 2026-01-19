<?php

namespace App\Http\Requests\DocumentType;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['required', 'boolean'],
            'requirement_conditions' => ['nullable', 'array'],
            'requirement_conditions.operator' => ['required_with:requirement_conditions', 'in:AND,OR'],
            'requirement_conditions.conditions' => ['required_with:requirement_conditions', 'array'],
            'requirement_conditions.conditions.*.field' => ['required', 'string'],
            'requirement_conditions.conditions.*.op' => ['required', 'in:=,!=,in,not_in,>,<,>=,<='],
            'requirement_conditions.conditions.*.value' => ['required'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
