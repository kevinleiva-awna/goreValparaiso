<?php

namespace App\Http\Requests\Public;

use App\Models\Consultation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Acceso a este endpoint requiere usuario autenticado, activo,
        // ciudadano y con correo verificado.
        $user = $this->user();
        return $user
            && $user->is_active
            && $user->isCitizen()
            && $user->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Validacion contextual: la consulta debe aceptar observaciones en
     * este momento y el metodo de autenticacion del usuario debe estar
     * habilitado para esta consulta.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $consultation = $this->route('consultation');
            if (! $consultation instanceof Consultation) {
                return;
            }

            // Estado del proceso + etapa activa
            if (! $consultation->isOpenForObservations()) {
                $validator->errors()->add('body',
                    'Este proceso no esta aceptando observaciones en este momento.');
                return;
            }

            // El metodo de autenticacion del ciudadano debe estar habilitado
            // por configuracion de la consulta (claveunica / manual).
            $authMethod = session('auth_method', 'manual');
            $allowedMethods = (array) ($consultation->auth_methods ?? []);
            if (! in_array($authMethod, $allowedMethods, true)) {
                $validator->errors()->add('body',
                    'Esta consulta no admite tu metodo de identificacion.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Tu observacion no puede estar vacia.',
            'body.min' => 'Tu observacion debe tener al menos 10 caracteres.',
            'body.max' => 'Tu observacion no puede superar los 10.000 caracteres.',
        ];
    }
}
