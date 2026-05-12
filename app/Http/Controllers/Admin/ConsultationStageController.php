<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsultationStageRequest;
use App\Http\Requests\Admin\UpdateConsultationStageRequest;
use App\Models\Consultation;
use App\Models\ConsultationStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsultationStageController extends Controller
{
    public function create(Consultation $consultation): View
    {
        return view('admin.consultations.stages.form', [
            'consultation' => $consultation,
            'stage' => new ConsultationStage([
                'status' => ConsultationStage::STATUS_PENDING,
                'accepts_observations' => true,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(StoreConsultationStageRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        // Posicion auto-asignada al final de las existentes
        $data['position'] = ($consultation->stages()->max('position') ?? 0) + 1;
        $data['consultation_id'] = $consultation->id;

        $consultation->stages()->create($data);

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Etapa creada correctamente.');
    }

    public function edit(Consultation $consultation, ConsultationStage $stage): View
    {
        return view('admin.consultations.stages.form', [
            'consultation' => $consultation,
            'stage' => $stage,
            'mode' => 'edit',
        ]);
    }

    public function update(
        UpdateConsultationStageRequest $request,
        Consultation $consultation,
        ConsultationStage $stage
    ): RedirectResponse {
        $stage->update($request->validated());

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Etapa actualizada correctamente.');
    }

    public function destroy(Consultation $consultation, ConsultationStage $stage): RedirectResponse
    {
        DB::transaction(function () use ($consultation, $stage) {
            $deletedPosition = $stage->position;
            $stage->delete();

            // Compactar posiciones para evitar gaps que rompan ordenes futuros
            $consultation->stages()
                ->where('position', '>', $deletedPosition)
                ->orderBy('position')
                ->each(function ($s) {
                    $s->decrement('position');
                });
        });

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Etapa eliminada correctamente.');
    }

    /**
     * Intercambia la posicion de una etapa con su vecina inmediata (arriba o abajo).
     * Rutas: POST /admin/consultations/{consultation}/stages/{stage}/move/{direction}
     */
    public function move(Consultation $consultation, ConsultationStage $stage, string $direction): RedirectResponse
    {
        // reorder() limpia el orderBy default de la relacion ($consultation->stages()
        // ordena por position ASC), permitiendo que nuestro DESC para 'up' funcione.
        $neighbor = $consultation->stages()
            ->reorder()
            ->where('position', $direction === 'up' ? '<' : '>', $stage->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($neighbor) {
            DB::transaction(function () use ($stage, $neighbor) {
                $stagePos = $stage->position;
                $neighborPos = $neighbor->position;
                // El unique (consultation_id, position) impide setear ambas a la misma
                // posicion durante el swap. Movemos $stage a un slot transitorio (0)
                // antes de hacer el intercambio definitivo.
                $stage->update(['position' => 0]);
                $neighbor->update(['position' => $stagePos]);
                $stage->update(['position' => $neighborPos]);
            });
        }

        return redirect()->route('admin.consultations.show', $consultation);
    }
}
