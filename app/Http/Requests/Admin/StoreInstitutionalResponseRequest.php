<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionalResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El middleware 'role:funcionario,super-admin' ya filtra acceso.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'La respuesta no puede estar vacia.',
            'content.min' => 'La respuesta debe tener al menos 10 caracteres.',
            'content.max' => 'La respuesta no puede superar los 5.000 caracteres.',
        ];
    }
}
