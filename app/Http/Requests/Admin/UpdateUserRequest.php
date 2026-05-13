<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'national_id' => ['required', 'string', new Rut(),
                Rule::unique('users', 'national_id')->ignore($userId)],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],

            // En edicion, password es opcional. Solo se actualiza si el
            // super-admin escribe uno nuevo.
            'password' => ['nullable', 'confirmed', Password::min(8)],

            'role' => ['required', Rule::in([User::ROLE_FUNCTIONARY, User::ROLE_SUPER_ADMIN])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => $this->filled('national_id')
                ? Rut::normalize($this->input('national_id'))
                : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    public function messages(): array
    {
        return [
            'national_id.unique' => 'Ya existe otro usuario con ese RUT.',
            'email.unique' => 'Ya existe otro usuario con ese correo.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'role.in' => 'El rol seleccionado no es valido.',
        ];
    }
}
