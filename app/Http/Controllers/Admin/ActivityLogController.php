<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Backoffice del audit log (D20) — restringido a super-admin.
 *
 * Permite visualizar las entradas registradas por spatie/laravel-activitylog
 * con filtros basicos. NO permite editar ni borrar (inmutabilidad).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest('id');

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }
        if ($request->filled('event') && in_array($request->input('event'), ['created', 'updated', 'deleted'], true)) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        $logNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->all();

        return view('admin.activity-log.index', [
            'activities' => $query->paginate(30)->withQueryString(),
            'logNames' => $logNames,
            'filters' => $request->only(['log_name', 'event', 'causer_id', 'from', 'to']),
        ]);
    }
}
