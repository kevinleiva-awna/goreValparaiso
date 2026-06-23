<x-mail::message>
# Recibimos tu participacion

Hola {{ $author }},

Tu participacion en el proceso **{{ $consultation->title }}** quedo registrada
correctamente en la plataforma del Gobierno Regional de Valparaiso, con
**{{ $observations->count() }}** {{ $observations->count() === 1 ? 'observacion' : 'observaciones' }}.

**Fecha de registro:** {{ $observations->first()->submitted_at->format('d/m/Y H:i') }} hrs (CLT)
**Metodo de identificacion:** @switch($observations->first()->auth_method_used)@case('claveunica')ClaveUnica@break @case('guest')Sin registro (invitado)@break @default {{ $observations->first()->auth_method_used }} @endswitch

---

@foreach ($observations as $obs)
## Observacion {{ $loop->iteration }} de {{ $observations->count() }}

**Codigo de seguimiento:** `{{ $obs->public_id }}`
@if ($obs->category)
**Tema:** {{ $obs->category }}
@endif
@if ($obs->subject)
**Asunto:** {{ $obs->subject }}
@endif

> {{ $obs->body }}

@if (! $loop->last)
---
@endif
@endforeach

---

@if ($observations->count() === 1)
Tu observacion sera revisada
@else
Tus observaciones seran revisadas
@endif
por la Unidad de Ordenamiento Territorial del Gobierno Regional. Si corresponde,
recibiras una respuesta institucional formal **a este mismo correo electronico**
al cierre del periodo de participacion. No es necesario que crees una cuenta
para recibirla.

<x-mail::button :url="route('public.consultations.show', $consultation->slug)">
Ver el proceso de consulta
</x-mail::button>

Si no enviaste esta participacion, por favor avisanos respondiendo a este correo.

Saludos,<br>
Gobierno Regional de Valparaiso
</x-mail::message>
