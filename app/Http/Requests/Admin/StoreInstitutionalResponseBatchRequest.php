<?php

namespace App\Http\Requests\Admin;

use App\Models\InstitutionalResponse;
use App\Models\Observation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionalResponseBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'observation_ids' => ['required', 'array', 'min:1', 'max:50'],
            'observation_ids.*' => ['integer', 'distinct', 'exists:observations,id'],
        ];
    }

    /**
     * Validacion extra: ninguna de las observaciones seleccionadas debe tener
     * ya una respuesta (ni siquiera en estado draft). Si la hay, el funcionario
     * debe gestionarla individualmente desde la ficha de la observacion.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $ids = (array) $this->input('observation_ids', []);
            if (empty($ids)) {
                return;
            }

            $withResponse = Observation::query()
                ->whereIn('id', $ids)
                ->whereHas('response')
                ->pluck('id')
                ->all();

            if (! empty($withResponse)) {
                $validator->errors()->add(
                    'observation_ids',
                    'Algunas observaciones seleccionadas ya tienen una respuesta. '
                    . 'Quita las observaciones con respuesta y vuelve a intentar. '
                    . 'IDs en conflicto: ' . implode(', ', $withResponse) . '.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'content.required' => 'La respuesta no puede estar vacia.',
            'content.min' => 'La respuesta debe tener al menos 10 caracteres.',
            'content.max' => 'La respuesta no puede superar los 5.000 caracteres.',
            'observation_ids.required' => 'Debes seleccionar al menos una observacion.',
            'observation_ids.min' => 'Debes seleccionar al menos una observacion.',
            'observation_ids.max' => 'No puedes responder mas de 50 observaciones en un solo lote.',
        ];
    }
}
