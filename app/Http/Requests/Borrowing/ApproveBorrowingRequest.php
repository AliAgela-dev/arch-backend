<?php

namespace App\Http\Requests\Borrowing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveBorrowingRequest extends FormRequest
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
        $minDays = config('borrowing.min_duration_days', 1);
        $maxDays = config('borrowing.max_duration_days', 30);

        return [
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
            'due_days' => [
                'nullable',
                'integer',
                "min:{$minDays}",
                "max:{$maxDays}",
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify an action (approve or reject).',
            'action.in' => 'Action must be either approve or reject.',
            'rejection_reason.required_if' => 'Rejection reason is required when rejecting a borrowing request.',
            'due_days.min' => 'Borrow duration must be at least :min days.',
            'due_days.max' => 'Borrow duration cannot exceed :max days.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'due_days' => 'borrow duration',
        ];
    }
}
