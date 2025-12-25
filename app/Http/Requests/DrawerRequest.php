<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DrawerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'cabinet_id' => 'required|string|exists:cabinets,id',
                'number'     => 'required|integer|min:1',
                'label'      => 'nullable|string|max:50',
                'capacity'   => 'nullable|integer|min:0',
                'status'     => 'required|in:active,inactive',
            ];
        }

        return [
            'cabinet_id' => 'sometimes|required|string|exists:cabinets,id',
            'number'     => 'sometimes|required|integer|min:1',
            'label'      => 'sometimes|nullable|string|max:50',
            'capacity'   => 'sometimes|required|integer|min:1',
            'status'     => 'sometimes|required|in:active,inactive',
        ];
    }
}
