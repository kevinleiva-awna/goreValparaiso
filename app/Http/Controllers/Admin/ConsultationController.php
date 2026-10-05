<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsultationRequest;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\ConsultationDocument;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived');

        $query = Consultation::query()
            ->when($showArchived, fn ($q) => $q->onlyTrashed())
            ->withCount('observations')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('instrument_type', $request->input('type'));
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $consultations = $query->paginate(15)->withQueryString();

        return view('admin.consultations.index', [
            'consultations' => $consultations,
            'filters' => $request->only(['status', 'type', 'q']),
            'showArchived' => $showArchived,
        ]);
    }

    public function create(): View
    {
        return view('admin.consultations.create', [
            'consultation' => new Consultation([
                'status' => Consultation::STATUS_DRAFT,
                'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
            ]),
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $consultation = Consultation::create($data);

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Consulta creada correctamente.');
    }

    public function show(Consultation $consultation): View
    {
        $consultation->load(['documents', 'creator']);
        $consultation->loadCount('observations');

        return view('admin.consultations.show', [
            'consultation' => $consultation,
        ]);
    }

    public function edit(Consultation $consultation): View
    {
        return view('admin.consultations.edit', [
            'consultation' => $consultation,
        ]);
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $consultation->update($data);

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Consulta actualizada correctamente.');
    }

    public function destroy(Consultation $consultation): RedirectResponse
    {
        $consultation->delete();

        return redirect()
            ->route('admin.consultations.index')
            ->with('status', 'Consulta archivada correctamente.');
    }

    public function restore(Consultation $consultation): RedirectResponse
    {
        $consultation->restore();

        return redirect()
            ->route('admin.consultations.index', ['archived' => 1])
            ->with('status', 'Consulta restaurada correctamente.');
    }

    /**
     * Paso previo a la eliminacion definitiva: detalla todo lo que se va a
     * perder y ofrece descargar el expediente antes. Solo super-admin y solo
     * sobre consultas ya archivadas.
     */
    public function confirmForceDelete(Consultation $consultation): View
    {
        abort_unless($consultation->trashed(), 404);

        return view('admin.consultations.force-delete', [
            'consultation' => $consultation,
            'impact' => $this->expedienteImpact($consultation),
        ]);
    }

    /**
     * Elimina para siempre una consulta archivada con todo su expediente:
     * observaciones (tambien las archivadas), respuestas, antecedentes y los
     * archivos en el storage. Las FKs de observaciones y respuestas son
     * restrictOnDelete, asi que se borra de abajo hacia arriba y con query
     * builder (sin eventos por fila); la bitacora recibe una sola entrada con
     * el resumen de lo eliminado.
     */
    public function forceDelete(Request $request, Consultation $consultation): RedirectResponse
    {
        abort_unless($consultation->trashed(), 404);

        $request->validate(['confirmacion' => ['required', 'in:ELIMINAR']], [
            'confirmacion.required' => 'Escribe ELIMINAR para confirmar.',
            'confirmacion.in' => 'Escribe ELIMINAR, en mayusculas, para confirmar.',
        ]);

        $impact = $this->expedienteImpact($consultation);
        $observations = Observation::withTrashed()->where('consultation_id', $consultation->id);
        $observationIds = (clone $observations)->pluck('id');
        $documents = ConsultationDocument::withTrashed()->where('consultation_id', $consultation->id);

        // Se juntan antes de borrar las filas: despues no quedaria de donde sacarlos.
        $files = (clone $observations)->whereNotNull('attachment_path')->get(['attachment_disk', 'attachment_path'])
            ->map(fn ($o) => [$o->attachment_disk, $o->attachment_path])
            ->concat((clone $documents)->get(['storage_disk', 'storage_path'])
                ->map(fn ($d) => [$d->storage_disk, $d->storage_path]));

        DB::transaction(function () use ($request, $consultation, $observations, $observationIds, $documents, $impact) {
            InstitutionalResponse::whereIn('observation_id', $observationIds)->delete();
            (clone $observations)->forceDelete();
            (clone $documents)->forceDelete();
            Consultation::withTrashed()->whereKey($consultation->id)->forceDelete();

            activity('consultation')
                ->performedOn($consultation)
                ->causedBy($request->user())
                ->event('deleted')
                ->withProperties(['attributes' => [
                    'title' => $consultation->title,
                    'slug' => $consultation->slug,
                    'eliminacion' => 'definitiva, con todo su expediente',
                ] + $impact])
                ->log('force_deleted');
        });

        // Los archivos se borran despues del commit: si la transaccion falla,
        // el expediente queda intacto. Un archivo que no se pueda borrar no
        // revierte nada; queda en el log para limpiarlo a mano.
        foreach ($files as [$disk, $path]) {
            try {
                Storage::disk($disk ?: config('filesystems.default'))->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Eliminacion de consulta: no se pudo borrar un archivo', [
                    'consultation_id' => $consultation->id,
                    'disk' => $disk,
                    'path' => $path,
                    'exception' => $e,
                ]);
            }
        }

        return redirect()
            ->route('admin.consultations.index', ['archived' => 1])
            ->with('status', sprintf(
                'Consulta "%s" eliminada definitivamente, con %d observacion(es) y %d antecedente(s).',
                $consultation->title,
                $impact['observaciones'],
                $impact['antecedentes'],
            ));
    }

    /**
     * Cuenta lo que arrastra la eliminacion definitiva de una consulta.
     *
     * @return array<string, int>
     */
    private function expedienteImpact(Consultation $consultation): array
    {
        $observations = Observation::withTrashed()->where('consultation_id', $consultation->id);
        $responses = InstitutionalResponse::whereIn('observation_id', (clone $observations)->select('id'));

        return [
            'observaciones' => (clone $observations)->count(),
            'observaciones_archivadas' => (clone $observations)->onlyTrashed()->count(),
            'respuestas_publicadas' => (clone $responses)->where('status', InstitutionalResponse::STATUS_PUBLISHED)->count(),
            'respuestas_borrador' => (clone $responses)->where('status', InstitutionalResponse::STATUS_DRAFT)->count(),
            'adjuntos' => (clone $observations)->whereNotNull('attachment_path')->count(),
            'adjuntos_bytes' => (int) (clone $observations)->sum('attachment_size_bytes'),
            'antecedentes' => ConsultationDocument::withTrashed()->where('consultation_id', $consultation->id)->count(),
        ];
    }
}
