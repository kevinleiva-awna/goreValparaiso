<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware role:super-admin ya restringe acceso. Esto es defensa en profundidad.
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'national_id' => ['required', 'string', new Rut(), 'unique:users,national_id'],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
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
            'national_id.required' => 'El RUT es obligatorio.',
            'national_id.unique' => 'Ya existe un usuario con ese RUT.',
            'name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato valido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'La contrasena es obligatoria.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'role.required' => 'Debes seleccionar un rol.',
            'role.in' => 'El rol seleccionado no es valido (solo funcionario o super-admin).',
        ];
    }
}
