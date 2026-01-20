<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
                'ends_with:@limu.edu.ly'
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'role' => ['nullable', 'string', Rule::enum(UserRole::class)],
            'status' => ['nullable', 'string', Rule::enum(UserStatus::class)],
            'faculties' => ['nullable', 'array', 'min:1'],
            'faculties.*' => ['required', 'integer', 'exists:faculties,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name must not exceed 255 characters.',

            'email.string' => 'Email must be a string.',
            'email.email' => 'Email must be a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'email.unique' => 'Email already exists.',
            'email.ends_with' => 'Email must end with @limu.edu.ly',

            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 8 characters long.',

            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.string' => 'Password confirmation must be a string.',

            'role.string' => 'Role must be a string.',
            'role.enum' => 'Role must be a valid role.',

            'status.string' => 'Status must be a string.',
            'status.enum' => 'Status must be a valid status.',

            'faculties.array' => 'Faculties must be an array.',
            'faculties.min' => 'At least one faculty is required.',
            'faculties.*.required' => 'Faculty ID is required.',
            'faculties.*.integer' => 'Faculty ID must be an integer.',
            'faculties.*.exists' => 'Selected faculty does not exist.',
        ];
    }
}
