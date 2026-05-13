<?php

namespace App\Http\Requests\Public\Auth;

use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cualquiera puede registrarse como ciudadano.
        return true;
    }

    public function rules(): array
    {
        return [
            'national_id' => ['required', 'string', new Rut(), 'unique:users,national_id'],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
            // Honeypot: campo invisible que solo bots completan
            'website' => ['nullable', 'size:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => $this->filled('national_id')
                ? Rut::normalize($this->input('national_id'))
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'national_id.required' => 'El RUT es obligatorio.',
            'national_id.unique' => 'Ya existe una cuenta con ese RUT. ¿Olvidaste tu contraseña?',
            'name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato valido.',
            'email.unique' => 'Ya existe una cuenta con ese correo. ¿Olvidaste tu contraseña?',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmacion de contraseña no coincide.',
            'terms.accepted' => 'Debes aceptar los terminos de uso para continuar.',
            'website.size' => 'Solicitud rechazada.', // honeypot, mensaje opaco
        ];
    }
}
