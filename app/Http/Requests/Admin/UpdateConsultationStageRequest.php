<?php

namespace App\Http\Requests\Admin;

use App\Models\ConsultationStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'accepts_observations' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([
                ConsultationStage::STATUS_PENDING,
                ConsultationStage::STATUS_ACTIVE,
                ConsultationStage::STATUS_CLOSED,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'accepts_observations' => $this->boolean('accepts_observations'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la etapa es obligatorio.',
            'status.required' => 'Debes seleccionar un estado.',
            'ends_at.after_or_equal' => 'La fecha de termino debe ser igual o posterior a la de inicio.',
        ];
    }
}
