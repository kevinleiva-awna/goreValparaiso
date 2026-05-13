<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ObservationsExport;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ObservationController extends Controller
{
    public function index(Request $request): View
    {
        $consultations = Consultation::query()
            ->orderBy('title')
            ->get(['id', 'slug', 'title']);

        $query = $this->buildFilteredQuery($request);

        return view('admin.observations.index', [
            'observations' => $query->paginate(20)->withQueryString(),
            'consultations' => $consultations,
            'filters' => $request->only(['consultation_id', 'stage_id', 'auth_method', 'from', 'to', 'q']),
        ]);
    }

    public function show(Observation $observation): View
    {
        $observation->load(['consultation', 'stage', 'user']);

        return view('admin.observations.show', [
            'observation' => $observation,
        ]);
    }

    public function export(Request $request, string $format = 'xlsx'): BinaryFileResponse
    {
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $query = $this->buildFilteredQuery($request);

        $filename = sprintf(
            'observaciones-gore-%s.%s',
            now()->format('Y-m-d_His'),
            $format,
        );

        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new ObservationsExport($query), $filename, $writerType);
    }

    /**
     * Construye la query base con todos los filtros del request. Reutilizado
     * por index() y export() para garantizar que el export refleja exactamente
     * lo que ve el funcionario en pantalla.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = Observation::query()
            ->with(['consultation:id,slug,title,instrument_type', 'stage:id,name'])
            ->latest('submitted_at');

        if ($request->filled('consultation_id')) {
            $query->where('consultation_id', $request->input('consultation_id'));
        }
        if ($request->filled('stage_id')) {
            $query->where('stage_id', $request->input('stage_id'));
        }
        if ($request->filled('auth_method') && in_array($request->input('auth_method'), ['claveunica', 'manual'], true)) {
            $query->where('auth_method_used', $request->input('auth_method'));
        }
        if ($request->filled('from')) {
            $query->where('submitted_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('submitted_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%")
                  ->orWhere('snapshot_national_id', 'like', "%{$term}%")
                  ->orWhere('snapshot_full_name', 'like', "%{$term}%")
                  ->orWhere('snapshot_email', 'like', "%{$term}%")
                  ->orWhere('public_id', $term);
            });
        }

        return $query;
    }
}
