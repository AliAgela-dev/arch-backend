<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DrawerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabinet_id' => 'required|string|exists:cabinets,id',
            'number'     => 'required|integer|min:1',
            'label'      => 'nullable|string|max:50',
            'capacity'   => 'nullable|integer|min:0',
            'status'     => 'required|in:active,inactive',
        ];
    }
}
