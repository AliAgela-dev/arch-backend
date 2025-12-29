<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CabinetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'      => 'sometimes|required|string|exists:rooms,id',
            'name'         => 'sometimes|required|string|max:255',
            'position_x'   => 'sometimes|required|numeric',
            'position_y'   => 'sometimes|required|numeric',
            // Prevent client from sending drawer_count (drawers are auto-generated).
            'drawer_count' => 'prohibited',
            'status'       => 'sometimes|required|in:active,inactive',
        ];
    }
}
