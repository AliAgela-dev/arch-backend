<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CabinetStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'      => 'required|string|exists:rooms,id',
            'name'         => 'required|string|max:255',
            'position_x'   => 'required|numeric',
            'position_y'   => 'required|numeric',
            // Prevent client from sending drawer_count (drawers are auto-generated).
            'drawer_count' => 'prohibited',
            'status'       => 'required|in:active,inactive',
        ];
    }
}
