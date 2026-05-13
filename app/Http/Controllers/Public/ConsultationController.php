<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultationController extends Controller
{
    private const DISK = 'local';

    /**
     * Listado publico de consultas. Solo expone procesos en estados
     * visibles para el ciudadano (los borradores quedan fuera).
     */
    public function index(Request $request): View
    {
        $visibleStatuses = [
            Consultation::STATUS_PUBLISHED,
            Consultation::STATUS_ACTIVE,
            Consultation::STATUS_CLOSED,
        ];

        $query = Consultation::query()
            ->whereIn('status', $visibleStatuses)
            ->withCount('observations')
            ->orderByRaw("FIELD(status, 'active', 'published', 'closed')")
            ->orderByDesc('starts_at');

        if ($request->filled('type')) {
            $query->where('instrument_type', $request->input('type'));
        }
        if ($request->filled('status') && in_array($request->input('status'), $visibleStatuses, true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('summary', 'like', "%{$term}%");
            });
        }

        return view('public.consultas.index', [
            'consultations' => $query->paginate(9)->withQueryString(),
            'filters' => $request->only(['type', 'status', 'q']),
        ]);
    }

    /**
     * Ficha publica de una consulta. Slug es identificador publico.
     */
    public function show(string $slug): View
    {
        $consultation = Consultation::query()
            ->whereIn('status', [
                Consultation::STATUS_PUBLISHED,
                Consultation::STATUS_ACTIVE,
                Consultation::STATUS_CLOSED,
            ])
            ->where('slug', $slug)
            ->with([
                'stages',
                'documents' => fn ($q) => $q->latest('version'),
            ])
            ->withCount('observations')
            ->firstOrFail();

        // Calcula si el ciudadano logueado puede enviar observacion ahora.
        // La vista usa este flag y el detalle del 'gatekeeper' para mostrar
        // el form, un CTA de login, o el motivo por el que no puede participar.
        $gatekeeper = $this->resolveSubmissionGate($consultation);

        return view('public.consultas.show', [
            'consultation' => $consultation,
            'isOpenForObservations' => $consultation->isOpenForObservations(),
            'gate' => $gatekeeper,
        ]);
    }

    /**
     * Resuelve el estado del "boton enviar observacion" para el usuario
     * actual y la consulta dada. Retorna un array con dos claves:
     *
     *   ['can' => bool, 'reason' => 'guest' | 'not_verified' | 'not_open'
     *                              | 'wrong_auth_method' | 'wrong_role' | null]
     *
     * Si can=true, reason=null. Si can=false, reason explica el bloqueo
     * para que la vista muestre el mensaje contextual correcto.
     */
    private function resolveSubmissionGate(Consultation $consultation): array
    {
        if (! auth()->check()) {
            return ['can' => false, 'reason' => 'guest'];
        }

        $user = auth()->user();

        if (! $user->isCitizen()) {
            return ['can' => false, 'reason' => 'wrong_role'];
        }

        if (! $user->hasVerifiedEmail()) {
            return ['can' => false, 'reason' => 'not_verified'];
        }

        if (! $consultation->isOpenForObservations()) {
            return ['can' => false, 'reason' => 'not_open'];
        }

        $authMethod = session('auth_method', 'manual');
        $allowed = (array) ($consultation->auth_methods ?? []);
        if (! in_array($authMethod, $allowed, true)) {
            return ['can' => false, 'reason' => 'wrong_auth_method'];
        }

        return ['can' => true, 'reason' => null];
    }

    /**
     * Descarga publica del documento vigente de un grupo de versiones.
     * URL usa file_group_id (UUID) para no exponer ids autoincrementales.
     * Resuelve a la version mas reciente no archivada del grupo, asi las
     * URLs viejas siguen funcionando aunque el doc haya sido reemplazado.
     */
    public function download(string $slug, string $fileGroupId): StreamedResponse
    {
        $consultation = Consultation::query()
            ->whereIn('status', [
                Consultation::STATUS_PUBLISHED,
                Consultation::STATUS_ACTIVE,
                Consultation::STATUS_CLOSED,
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $document = $consultation->documents()
            ->where('file_group_id', $fileGroupId)
            ->latest('version')
            ->firstOrFail();

        abort_unless(Storage::disk(self::DISK)->exists($document->storage_path), 404);

        return Storage::disk(self::DISK)->download(
            $document->storage_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
    }
}
