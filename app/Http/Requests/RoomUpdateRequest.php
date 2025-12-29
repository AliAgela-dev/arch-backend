<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'canvas_data' => 'sometimes|nullable|string',
            'status'      => 'sometimes|required|in:active,inactive',
        ];
    }
}
