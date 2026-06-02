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

        // CASE WHEN en lugar de FIELD() para soporte cross-DB (MariaDB en prod,
        // SQLite en tests). Resultado: activas primero, despues publicadas, despues cerradas.
        $query = Consultation::query()
            ->whereIn('status', $visibleStatuses)
            ->withCount('observations')
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'published' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END")
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

        // Respuestas institucionales publicadas para esta consulta. Se exponen
        // solo las que tienen status=published y se evita filtrar RUT/email
        // del ciudadano en la vista publica.
        $publishedResponses = $consultation->observations()
            ->whereHas('response', fn ($q) => $q->where('status', 'published'))
            ->with([
                'response' => fn ($q) => $q->with('responder:id,name,last_name'),
                'stage:id,name',
            ])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'respuestas');

        // Calcula si el ciudadano logueado puede enviar observacion ahora.
        // La vista usa este flag y el detalle del 'gatekeeper' para mostrar
        // el form, un CTA de login, o el motivo por el que no puede participar.
        $gatekeeper = $this->resolveSubmissionGate($consultation);

        return view('public.consultas.show', [
            'consultation' => $consultation,
            'isOpenForObservations' => $consultation->isOpenForObservations(),
            'gate' => $gatekeeper,
            'publishedResponses' => $publishedResponses,
        ]);
    }

    /**
     * Resuelve el estado del "boton enviar observacion" para el usuario
     * actual y la consulta dada. Retorna:
     *
     *   ['can' => bool,
     *    'mode' => 'auth' | 'guest' | null,
     *    'reason' => 'guest' | 'not_open' | 'wrong_auth_method'
     *              | 'wrong_role' | null]
     *
     * Si can=true, mode='auth' o 'guest' y reason=null.
     * Si can=false, mode=null y reason explica el bloqueo.
     *
     * Nota: 'not_verified' se elimino en junio 2026 con la eliminacion del
     * registro manual. ClaveUnica ya entrega usuarios con email verificado
     * por el Estado, asi que !hasVerifiedEmail() no deberia ocurrir.
     */
    private function resolveSubmissionGate(Consultation $consultation): array
    {
        if (! auth()->check()) {
            // Sin login pero la consulta admite participacion como invitado:
            // gate abierto en modo guest (la vista muestra inputs nombre+email).
            if ($consultation->allowsGuest() && $consultation->isOpenForObservations()) {
                return ['can' => true, 'mode' => 'guest', 'reason' => null];
            }
            return ['can' => false, 'mode' => null, 'reason' => 'guest'];
        }

        $user = auth()->user();

        if (! $user->isCitizen()) {
            return ['can' => false, 'mode' => null, 'reason' => 'wrong_role'];
        }

        if (! $consultation->isOpenForObservations()) {
            return ['can' => false, 'mode' => null, 'reason' => 'not_open'];
        }

        $authMethod = session('auth_method', 'claveunica');
        $allowed = (array) ($consultation->auth_methods ?? []);
        if (! in_array($authMethod, $allowed, true)) {
            return ['can' => false, 'mode' => null, 'reason' => 'wrong_auth_method'];
        }

        return ['can' => true, 'mode' => 'auth', 'reason' => null];
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
