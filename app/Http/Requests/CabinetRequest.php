<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CabinetRequest extends FormRequest
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
                'room_id'      => 'required|string|exists:rooms,id',
                'name'         => 'required|string|max:255',
                'position_x'   => 'required|numeric',
                'position_y'   => 'required|numeric',
                'drawer_count' => 'prohibited',
                'status'       => 'required|in:active,inactive',
            ];
        }

        return [
            'room_id'      => 'sometimes|required|string|exists:rooms,id',
            'name'         => 'sometimes|required|string|max:255',
            'position_x'   => 'sometimes|required|numeric',
            'position_y'   => 'sometimes|required|numeric',
            'drawer_count' => 'prohibited',
            'status'       => 'sometimes|required|in:active,inactive',
        ];
    }
}
