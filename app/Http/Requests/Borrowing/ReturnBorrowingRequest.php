<?php

namespace App\Http\Requests\Borrowing;

use Illuminate\Foundation\Http\FormRequest;

class ReturnBorrowingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by policy, so return true here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'admin_notes.max' => 'Admin notes must not exceed 1000 characters.',
        ];
    }
}
