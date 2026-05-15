<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstitutionalResponseBatchRequest;
use App\Http\Requests\Admin\StoreInstitutionalResponseRequest;
use App\Mail\InstitutionalResponsePublished;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Gestion de respuestas institucionales a observaciones ciudadanas (D14).
 *
 * Flujo principal:
 *  - store/update mantienen una respuesta en estado draft mientras se
 *    redacta. publish dispara la notificacion al ciudadano por correo.
 *  - destroyDraft permite descartar borradores. Las respuestas publicadas
 *    son inmutables.
 *  - batchCreate/batchStore permiten responder con un mismo texto a varias
 *    observaciones a la vez. Todas comparten un mismo batch_id (UUID).
 */
class InstitutionalResponseController extends Controller
{
    public function store(StoreInstitutionalResponseRequest $request, Observation $observation): RedirectResponse
    {
        abort_if($observation->response()->exists(), 422,
            'Esta observacion ya tiene una respuesta institucional.');

        $publishNow = $request->boolean('publish');
        $now = now();

        $response = InstitutionalResponse::create([
            'observation_id' => $observation->id,
            'content' => $request->validated('content'),
            'responded_by' => $request->user()->id,
            'responded_at' => $now,
            'status' => $publishNow
                ? InstitutionalResponse::STATUS_PUBLISHED
                : InstitutionalResponse::STATUS_DRAFT,
            'published_at' => $publishNow ? $now : null,
        ]);

        if ($publishNow) {
            Mail::to($observation->snapshot_email)
                ->queue(new InstitutionalResponsePublished($observation, $response));
        }

        return redirect()
            ->route('admin.observations.show', $observation)
            ->with('status', $publishNow
                ? 'Respuesta publicada. Se notifico al ciudadano por correo.'
                : 'Borrador de respuesta guardado.');
    }

    public function update(StoreInstitutionalResponseRequest $request, Observation $observation): RedirectResponse
    {
        $response = $observation->response()->firstOrFail();

        abort_if($response->isPublished(), 422,
            'No puedes editar una respuesta ya publicada.');

        $response->update([
            'content' => $request->validated('content'),
        ]);

        return redirect()
            ->route('admin.observations.show', $observation)
            ->with('status', 'Borrador actualizado.');
    }

    public function publish(Observation $observation): RedirectResponse
    {
        $response = $observation->response()->firstOrFail();

        if (! $response->isPublished()) {
            $response->update([
                'status' => InstitutionalResponse::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            Mail::to($observation->snapshot_email)
                ->queue(new InstitutionalResponsePublished($observation, $response->fresh()));
        }

        return redirect()
            ->route('admin.observations.show', $observation)
            ->with('status', 'Respuesta publicada. Se notifico al ciudadano por correo.');
    }

    public function destroyDraft(Observation $observation): RedirectResponse
    {
        $response = $observation->response()->firstOrFail();

        abort_if($response->isPublished(), 422,
            'No puedes eliminar una respuesta ya publicada.');

        $response->delete();

        return redirect()
            ->route('admin.observations.show', $observation)
            ->with('status', 'Borrador descartado.');
    }

    /**
     * Muestra el formulario de respuesta por lote. Acepta observation_ids[]
     * via query string (POST desde la barra de acciones masivas) o GET (link directo).
     */
    public function batchCreate(): View
    {
        $ids = collect(request('observation_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $observations = Observation::query()
            ->whereIn('id', $ids)
            ->with(['consultation:id,title', 'stage:id,name'])
            ->get();

        $alreadyResponded = $observations
            ->filter(fn (Observation $o) => $o->response()->exists())
            ->pluck('id')
            ->all();

        return view('admin.observations.batch', [
            'observations' => $observations,
            'alreadyResponded' => $alreadyResponded,
        ]);
    }

    public function batchStore(StoreInstitutionalResponseBatchRequest $request): RedirectResponse
    {
        $batchId = (string) Str::uuid();
        $content = $request->validated('content');
        $ids = $request->validated('observation_ids');
        $userId = $request->user()->id;

        $observations = Observation::query()->whereIn('id', $ids)->get();

        DB::transaction(function () use ($observations, $content, $batchId, $userId) {
            $now = now();
            foreach ($observations as $observation) {
                InstitutionalResponse::create([
                    'observation_id' => $observation->id,
                    'content' => $content,
                    'batch_id' => $batchId,
                    'responded_by' => $userId,
                    'responded_at' => $now,
                    'status' => InstitutionalResponse::STATUS_PUBLISHED,
                    'published_at' => $now,
                ]);
            }
        });

        // Notificar fuera de la transaccion para no encolar mails si el commit falla.
        foreach ($observations as $observation) {
            $response = $observation->response()->first();
            Mail::to($observation->snapshot_email)
                ->queue(new InstitutionalResponsePublished($observation, $response));
        }

        return redirect()
            ->route('admin.observations.index')
            ->with('status', sprintf(
                '%d respuesta(s) publicada(s) en lote. Lote %s.',
                $observations->count(),
                Str::limit($batchId, 8, ''),
            ));
    }
}
