<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Panel de inicio del backoffice con las metricas exigidas por las EETT:
 * total de observaciones, observaciones por proceso y observaciones por dia.
 *
 * Todo se calcula con agregados en base de datos, no cargando colecciones a
 * memoria: durante un proceso con afluencia real la tabla de observaciones
 * llega a varios miles de filas.
 */
class DashboardController extends Controller
{
    /** Ventana del grafico diario. */
    private const DIAS = 30;

    public function __invoke(): View
    {
        return view('dashboard', [
            'total' => Observation::count(),
            'sinRespuesta' => Observation::whereDoesntHave('response')->count(),
            'procesosActivos' => $this->procesosActivos(),
            'porProceso' => $this->porProceso(),
            'porDia' => $this->porDia(),
            'dias' => self::DIAS,
        ]);
    }

    /**
     * Procesos que hoy admiten participacion: estado activo y "ahora" dentro de
     * su ventana. Mismo criterio que Consultation::isOpenForObservations, en
     * version consulta para no traer los modelos.
     */
    private function procesosActivos(): int
    {
        return Consultation::query()
            ->where('status', Consultation::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->count();
    }

    /**
     * Observaciones por proceso, de mayor a menor.
     *
     * withTrashed en las consultas: una consulta archivada conserva sus
     * observaciones y estas siguen sumando al total. Si se excluyeran, la suma
     * del desglose no cuadraria con el total y el funcionario no tendria como
     * explicar la diferencia.
     */
    private function porProceso(): Collection
    {
        return Consultation::withTrashed()
            ->withCount('observations')
            ->orderByDesc('observations_count')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'status', 'deleted_at'])
            ->filter(fn (Consultation $c) => $c->observations_count > 0)
            ->values();
    }

    /**
     * Serie diaria de los ultimos DIAS dias, con los dias sin observaciones
     * rellenados en cero. Sin el relleno el grafico comprimiria el eje temporal
     * y un periodo inactivo se leeria como actividad continua.
     *
     * @return Collection<int, array{fecha: Carbon, total: int}>
     */
    private function porDia(): Collection
    {
        $desde = now()->subDays(self::DIAS - 1)->startOfDay();

        $conteos = Observation::query()
            ->where('submitted_at', '>=', $desde)
            ->selectRaw('DATE(submitted_at) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        return collect(range(0, self::DIAS - 1))->map(function (int $i) use ($desde, $conteos) {
            $fecha = $desde->copy()->addDays($i);

            return [
                'fecha' => $fecha,
                'total' => (int) ($conteos[$fecha->format('Y-m-d')] ?? 0),
            ];
        });
    }
}
