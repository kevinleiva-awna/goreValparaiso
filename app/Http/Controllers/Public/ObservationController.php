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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $attachmentMeta = [];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            // Nombre aleatorio en el storage; conservamos el nombre original
            // como metadato para mostrarlo al ciudadano y al funcionario.
            $stored = $file->store(
                'observations/' . $consultation->id,
                ['disk' => 's3']
            );
            $attachmentMeta = [
                'attachment_path' => $stored,
                'attachment_original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'attachment_mime_type' => $file->getMimeType(),
                'attachment_size_bytes' => $file->getSize(),
            ];
        }

        // Branchea segun haya usuario logueado o sea participacion guest.
        // El FormRequest ya garantizo que el camino guest solo se llega si
        // la consulta tiene 'guest' en auth_methods.
        $identityBranch = $user
            ? [
                'user_id' => $user->id,
                'auth_method_used' => session('auth_method', Observation::AUTH_MANUAL),
                'snapshot_national_id' => $user->national_id,
                'snapshot_full_name' => trim($user->name . ' ' . $user->last_name),
                'snapshot_email' => $user->email,
            ]
            : [
                'user_id' => null,
                'auth_method_used' => Observation::AUTH_GUEST,
                'snapshot_national_id' => null,
                'snapshot_full_name' => $data['guest_name'],
                'snapshot_email' => $data['guest_email'],
            ];

        $observation = Observation::create([
            'consultation_id' => $consultation->id,
            'stage_id' => $stage->id,

            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'category' => $data['category'] ?? null,

            // Trazabilidad operativa
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),

            ...$identityBranch,
            ...$attachmentMeta,
        ]);

        // Mail de confirmacion al autor (user logueado o guest auto-declarado).
        $emailTo = $user ? $user->email : $data['guest_email'];
        Mail::to($emailTo)->queue(new ObservationSubmitted($observation));

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

        // Observacion con usuario: solo el autor logueado puede verla
        // (evita filtrar el body via URL adivinada).
        // Observacion guest: cualquiera con el UUID del publicId puede verla;
        // el UUID es secreto-suficiente para la pagina de confirmacion (no
        // expone datos sensibles mas alla del propio body) y el ciudadano
        // accede via redirect post-submit o por el link del mail.
        if ($observation->user_id !== null) {
            abort_unless(auth()->id() === $observation->user_id, 404);
        }

        return view('public.observations.success', [
            'observation' => $observation,
            'consultation' => $observation->consultation,
        ]);
    }
}
