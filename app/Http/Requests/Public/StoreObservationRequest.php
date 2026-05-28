<?php

namespace App\Http\Requests\Public;

use App\Models\Consultation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Camino autenticado: usuario activo, ciudadano, email verificado.
        if ($user) {
            return $user->is_active
                && $user->isCitizen()
                && $user->hasVerifiedEmail();
        }

        // Camino guest: solo si la consulta lo habilita explicitamente.
        $consultation = $this->route('consultation');
        return $consultation instanceof Consultation
            && $consultation->allowsGuest();
    }

    public function rules(): array
    {
        $isGuest = ! $this->user();

        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            // Opcional. 10 MB max. Tipos pensados para antecedentes
            // ciudadanos comunes: PDF, imagen, oficina y CAD ligero.
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,odt,ods,txt',
            ],
            // Guest: nombre + email requeridos (auto-declarados, sin verificar).
            // Auth: ignorados — el snapshot sale del modelo User.
            'guest_name' => [$isGuest ? 'required' : 'prohibited', 'string', 'max:150'],
            'guest_email' => [$isGuest ? 'required' : 'prohibited', 'email', 'max:255'],
        ];
    }

    /**
     * Validacion contextual: la consulta debe aceptar observaciones en
     * este momento y el metodo (auth o guest) debe estar habilitado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $consultation = $this->route('consultation');
            if (! $consultation instanceof Consultation) {
                return;
            }

            // Estado del proceso + etapa activa.
            if (! $consultation->isOpenForObservations()) {
                $validator->errors()->add('body',
                    'Este proceso no esta aceptando observaciones en este momento.');
                return;
            }

            $allowedMethods = (array) ($consultation->auth_methods ?? []);

            // Sin login: chequear que la consulta admita guest.
            if (! $this->user()) {
                if (! in_array(Consultation::AUTH_GUEST, $allowedMethods, true)) {
                    $validator->errors()->add('body',
                        'Esta consulta no admite comentarios sin registro.');
                }
                return;
            }

            // Con login: chequear que el metodo de la sesion este habilitado.
            $authMethod = session('auth_method', 'manual');
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
            'attachment.max' => 'El archivo no puede superar los 10 MB.',
            'attachment.mimes' => 'Formato no permitido. Adjunta PDF, imagen, Word, Excel o texto plano.',
            'guest_name.required' => 'Tu nombre es obligatorio para identificar la observacion.',
            'guest_email.required' => 'Tu correo electronico es obligatorio.',
            'guest_email.email' => 'El correo electronico no tiene un formato valido.',
        ];
    }
}
