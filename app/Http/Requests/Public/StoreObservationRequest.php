<?php

namespace App\Http\Requests\Public;

use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas del envio de observacion publica. Soporta tres caminos:
 *
 *  - Usuario autenticado por ClaveUnica (siempre actor='natural').
 *  - Guest 'natural' (Persona Natural sin ClaveUnica): nombre, email, RUT
 *    o pasaporte. Telefono/comuna/edad opcionales.
 *  - Guest 'pj' (Persona Juridica) o 'org' (Organizacion sin PJ): razon
 *    social, email, RUT de la empresa. Nombre fantasia/telefono/direccion
 *    opcionales.
 *
 * El campo `actor_type` SOLO se valida para guest. Para usuarios autenticados
 * el actor siempre es 'natural' y el controller lo fuerza.
 */
class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Camino autenticado: usuario activo, ciudadano, email verificado
        // (ClaveUnica entrega email_verified_at=now() al crear el User).
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
        $actorType = $this->input('actor_type');
        $isPJ = $isGuest && in_array($actorType, [Observation::ACTOR_PJ, Observation::ACTOR_ORG], true);
        $isNaturalGuest = $isGuest && $actorType === Observation::ACTOR_NATURAL;

        return [
            // Comunes a todos los caminos.
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            // 10 MB max. Tipos pensados para antecedentes ciudadanos comunes:
            // PDF, imagen, oficina y CAD ligero.
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,odt,ods,txt',
            ],

            // Selector de tipo de actor. Obligatorio para guest. Auth siempre PN.
            'actor_type' => [
                $isGuest ? 'required' : 'nullable',
                Rule::in([Observation::ACTOR_NATURAL, Observation::ACTOR_PJ, Observation::ACTOR_ORG]),
            ],

            // === Guest Persona Natural ===
            'guest_name' => [Rule::requiredIf($isNaturalGuest), 'nullable', 'string', 'max:150'],
            'guest_id_type' => [
                Rule::requiredIf($isNaturalGuest),
                'nullable',
                Rule::in([Observation::ID_TYPE_RUT, Observation::ID_TYPE_PASSPORT]),
            ],
            'guest_national_id' => [Rule::requiredIf($isNaturalGuest), 'nullable', 'string', 'max:12'],
            'guest_comuna' => ['nullable', 'string', 'max:100'],
            'guest_age' => ['nullable', 'integer', 'min:14', 'max:120'],

            // === Guest Persona Juridica / Organizacion sin PJ ===
            'guest_legal_name' => [Rule::requiredIf($isPJ), 'nullable', 'string', 'max:200'],
            'guest_business_id' => [Rule::requiredIf($isPJ), 'nullable', 'string', 'max:12'],
            'guest_trade_name' => ['nullable', 'string', 'max:200'],
            'guest_address' => ['nullable', 'string', 'max:255'],

            // === Comunes a todos los caminos guest ===
            'guest_email' => [Rule::requiredIf($isGuest), 'nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
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
            $authMethod = session('auth_method', 'claveunica');
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
            'actor_type.required' => 'Debes indicar si participas como Persona Natural, Persona Juridica u Organizacion sin PJ.',
            'actor_type.in' => 'Tipo de participante invalido.',
            'guest_name.required' => 'Tu nombre es obligatorio para identificar la observacion.',
            'guest_id_type.required' => 'Debes indicar el tipo de identificacion (RUT o pasaporte).',
            'guest_national_id.required' => 'Tu numero de identificacion es obligatorio.',
            'guest_legal_name.required' => 'La razon social es obligatoria para personas juridicas y organizaciones.',
            'guest_business_id.required' => 'El RUT de la entidad es obligatorio.',
            'guest_email.required' => 'Tu correo electronico es obligatorio.',
            'guest_email.email' => 'El correo electronico no tiene un formato valido.',
            'guest_age.integer' => 'Tu edad debe ser un numero.',
            'guest_age.min' => 'La edad minima para participar es 14 anos.',
        ];
    }
}
