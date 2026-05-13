<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreObservationRequest;
use App\Mail\ObservationSubmitted;
use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\Observation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ObservationController extends Controller
{
    public function store(StoreObservationRequest $request, Consultation $consultation): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Etapa activa que admite observaciones en el momento del envio.
        // El isOpenForObservations() del FormRequest ya valido que exista.
        $stage = $consultation->stages()
            ->where('accepts_observations', true)
            ->where('status', ConsultationStage::STATUS_ACTIVE)
            ->firstOrFail();

        $observation = Observation::create([
            'consultation_id' => $consultation->id,
            'stage_id' => $stage->id,
            'user_id' => $user->id,

            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'category' => $data['category'] ?? null,

            // Metodo de auth en la sesion vigente. T4.3 lo setea a 'manual'.
            // T4.2 (ClaveUnica) lo seteara a 'claveunica' al completar el OIDC.
            'auth_method_used' => session('auth_method', Observation::AUTH_MANUAL),

            // Snapshot inalterable de identidad. Si el usuario edita su perfil
            // despues, la observacion conserva lo que era cierto al enviarla.
            'snapshot_national_id' => $user->national_id,
            'snapshot_full_name' => trim($user->name . ' ' . $user->last_name),
            'snapshot_email' => $user->email,

            // Trazabilidad operativa
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        // Mail de confirmacion encolado (queue driver database). El usuario
        // recibe respuesta inmediata, el mail se procesa async.
        Mail::to($user->email)->queue(new ObservationSubmitted($observation));

        return redirect()->route('public.observations.success', [
            'slug' => $consultation->slug,
            'publicId' => $observation->public_id,
        ]);
    }

    public function success(string $slug, string $publicId): View
    {
        $observation = Observation::query()
            ->where('public_id', $publicId)
            ->whereHas('consultation', fn ($q) => $q->where('slug', $slug))
            ->with('consultation')
            ->firstOrFail();

        // Solo el autor puede ver su pagina de exito (evita filtrar el body
        // de observaciones de otros via URL adivinada).
        abort_unless(auth()->id() === $observation->user_id, 404);

        return view('public.observations.success', [
            'observation' => $observation,
            'consultation' => $observation->consultation,
        ]);
    }
}
