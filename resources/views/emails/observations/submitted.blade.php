<x-mail::message>
# Recibimos tu observacion

Hola {{ $observation->snapshot_full_name }},

Tu observacion al proceso **{{ $consultation->title }}** quedo registrada
correctamente en la plataforma del Gobierno Regional de Valparaiso.

**Codigo de seguimiento:** `{{ $observation->public_id }}`
**Fecha de registro:** {{ $observation->submitted_at->format('d/m/Y H:i') }} hrs (CLT)
**Metodo de identificacion:** {{ $observation->auth_method_used === 'claveunica' ? 'ClaveUnica' : 'Registro manual' }}

---

## Resumen de tu observacion

@if ($observation->subject)
**Asunto:** {{ $observation->subject }}
@endif

@if ($observation->category)
**Categoria:** {{ $observation->category }}
@endif

> {{ $observation->body }}

---

Tu observacion sera revisada por la Unidad de Ordenamiento Territorial del
Gobierno Regional. Si corresponde, recibiras una respuesta institucional
formal al cierre del periodo de participacion.

<x-mail::button :url="route('public.consultations.show', $consultation->slug)">
Ver el proceso de consulta
</x-mail::button>

Si no enviaste esta observacion, por favor avisanos respondiendo a este
correo.

Saludos,<br>
Gobierno Regional de Valparaiso
</x-mail::message>
