<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DrawerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabinet_id' => 'sometimes|required|string|exists:cabinets,id',
            'number'     => 'sometimes|required|integer|min:1',
            'label'      => 'sometimes|nullable|string|max:50',
            'capacity'   => 'sometimes|required|integer|min:1',
            'status'     => 'sometimes|required|in:active,inactive',
        ];
    }
}
