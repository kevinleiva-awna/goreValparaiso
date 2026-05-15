<x-mail::message>
# Tenemos una respuesta a tu observacion

Hola {{ $observation->snapshot_full_name }},

El Gobierno Regional de Valparaiso publico una respuesta institucional
a la observacion que enviaste al proceso **{{ $consultation->title }}**.

**Codigo de tu observacion:** `{{ $observation->public_id }}`
**Fecha de envio original:** {{ $observation->submitted_at->format('d/m/Y H:i') }} hrs (CLT)
**Fecha de respuesta:** {{ $response->published_at->format('d/m/Y H:i') }} hrs (CLT)

---

## Respuesta de la Unidad de Ordenamiento Territorial

> {{ $response->content }}

---

@if ($observation->subject)
**Recordatorio del asunto de tu observacion:** {{ $observation->subject }}
@endif

Puedes consultar la respuesta junto al resto del expediente publico del
proceso en el siguiente enlace:

<x-mail::button :url="route('public.consultations.show', $consultation->slug)">
Ver el proceso de consulta
</x-mail::button>

Si tienes dudas, puedes responder a este correo y un funcionario te
contactara.

Saludos,<br>
Gobierno Regional de Valparaiso
</x-mail::message>
