<?php

namespace App\Http\Requests\Admin;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El middleware 'role' ya garantiza funcionario o super-admin.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash',
                Rule::unique('consultations', 'slug')],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'instrument_type' => ['required', Rule::in([
                Consultation::TYPE_IPT,
                Consultation::TYPE_PROT,
                Consultation::TYPE_ZUBC,
                Consultation::TYPE_OTHER,
            ])],
            'status' => ['required', Rule::in([
                Consultation::STATUS_DRAFT,
                Consultation::STATUS_PUBLISHED,
                Consultation::STATUS_ACTIVE,
                Consultation::STATUS_CLOSED,
                Consultation::STATUS_ARCHIVED,
            ])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'auth_methods' => ['required', 'array', 'min:1'],
            'auth_methods.*' => [Rule::in([
                Consultation::AUTH_CLAVEUNICA,
                Consultation::AUTH_MANUAL,
                Consultation::AUTH_GUEST,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si el funcionario no entrega slug, lo derivamos del titulo
        // y agregamos un sufijo aleatorio corto para garantizar unicidad.
        if (! $this->filled('slug') && $this->filled('title')) {
            $base = Str::slug($this->input('title'));
            $this->merge([
                'slug' => $base . '-' . Str::lower(Str::random(4)),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo es obligatorio.',
            'slug.unique' => 'Ya existe una consulta con ese slug.',
            'slug.alpha_dash' => 'El slug solo puede contener letras, numeros, guiones y guion bajo.',
            'instrument_type.required' => 'Debes seleccionar el tipo de instrumento.',
            'status.required' => 'Debes seleccionar un estado.',
            'ends_at.after_or_equal' => 'La fecha de termino debe ser igual o posterior a la de inicio.',
            'auth_methods.required' => 'Debes habilitar al menos un metodo de autenticacion.',
            'auth_methods.min' => 'Debes habilitar al menos un metodo de autenticacion.',
        ];
    }
}
