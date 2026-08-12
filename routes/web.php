<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ConsultationDocumentController;
use App\Http\Controllers\Admin\InstitutionalResponseController;
use App\Http\Controllers\Admin\ObservationController as AdminObservationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Dev\MockClaveUnicaController;
use App\Http\Controllers\Public\Auth\ClaveUnicaController;
use App\Http\Controllers\Public\ConsultationController as PublicConsultationController;
use App\Http\Controllers\Public\ObservationController as PublicObservationController;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    // Procesos para el hero/listado del home: publicados (proximos) o activos
    // DENTRO de su ventana. Una consulta activa cuya fecha de termino ya paso
    // no es vigente y no debe figurar como "en curso" / "0 dias restantes"
    // (mismo criterio que Consultation::effectiveStatus en /consultas; acta
    // jun 2026, bug #1). Las 3 mas recientes.
    $featured = Consultation::query()
        ->where(function ($q) {
            $q->where('status', Consultation::STATUS_PUBLISHED)
              ->orWhere(fn ($a) => $a
                  ->where('status', Consultation::STATUS_ACTIVE)
                  ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now())));
        })
        ->withCount('observations')
        ->orderByDesc('starts_at')
        ->limit(3)
        ->get();

    // Stats agregados para el hero (acta junio 2026, punto 1: mas llamativo).
    $stats = [
        'active_processes' => Consultation::query()
            ->where('status', Consultation::STATUS_ACTIVE)
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->count(),
        'total_observations' => \App\Models\Observation::query()->count(),
        'closed_processes' => Consultation::query()
            ->where('status', Consultation::STATUS_CLOSED)
            ->count(),
    ];

    return view('welcome', ['featured' => $featured, 'stats' => $stats]);
})->name('home');

// Health-check enriquecido: verifica conectividad a BD y al disk de storage
// configurado. Sin auth (para que el load balancer / monitoring lo consulte)
// pero rate-limited a 30/min por IP para evitar abuso. Distinto a /up (que
// Laravel ya provee y solo valida bootstrap).
Route::get('/healthz', function () {
    $startedAt = microtime(true);
    $dbOk = false;
    $storageOk = false;
    $dbError = null;
    $storageError = null;

    try {
        DB::connection()->getPdo();
        DB::select('SELECT 1');
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
    }

    try {
        $disk = config('filesystems.default');
        // Touch no-op: probamos lectura del root listing en disk default.
        Storage::disk($disk)->files('healthz');
        $storageOk = true;
    } catch (\Throwable $e) {
        $storageError = $e->getMessage();
    }

    $status = ($dbOk && $storageOk) ? 'ok' : 'degraded';
    $code = ($dbOk && $storageOk) ? 200 : 503;

    return response()->json([
        'status' => $status,
        'checks' => [
            'database' => ['ok' => $dbOk, 'error' => $dbError],
            'storage' => ['ok' => $storageOk, 'disk' => config('filesystems.default'), 'error' => $storageError],
        ],
        'app_env' => config('app.env'),
        'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
    ], $code);
})->middleware('throttle:30,1')->name('healthz');

// Portal Ciudadano (publico, sin auth)
Route::prefix('consultas')->group(function () {
    Route::get('/', [PublicConsultationController::class, 'index'])->name('public.consultations.index');
    Route::get('/{slug}', [PublicConsultationController::class, 'show'])->name('public.consultations.show');
    Route::get('/{slug}/antecedentes/{fileGroupId}/descargar',
        [PublicConsultationController::class, 'download'])
        ->name('public.consultations.documents.download');

    // Envio de observaciones: requiere ciudadano autenticado y verificado.
    // El StoreObservationRequest::authorize() valida nuevamente.
    // Rate limit: 5 envios por minuto por IP+usuario (anti-flood).
    // Sin middleware 'auth' aqui: el FormRequest decide quien puede postear.
    // - Si hay user y es citizen verificado -> permitido (camino normal).
    // - Si no hay user pero la consulta admite 'guest' -> permitido sin login.
    // Cualquier otro caso lo rechaza authorize() con 403.
    Route::post('/{consultation:slug}/observaciones',
        [PublicObservationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('public.observations.store');

    // SIN middleware 'auth': los invitados (guest) también llegan aquí tras
    // enviar. Con 'auth', al guest lo rebotaba al login del backoffice (unica
    // ruta 'login') y ahi quedaba varado. La autorizacion la aplica el propio
    // success(): observacion con user -> solo su autor (404 para el resto);
    // observacion guest -> accesible por el UUID secreto del publicId.
    Route::get('/{slug}/observaciones/{publicId}/exito',
        [PublicObservationController::class, 'success'])
        ->name('public.observations.success');
});

// Auth ciudadana: SOLO ClaveUnica. El registro manual con email/password
// fue eliminado en junio 2026 (acta de observaciones GORE, punto 2): el
// flujo "sin ClaveUnica" se atiende como guest dentro del formulario de
// observacion, no via cuenta de usuario.
Route::middleware('guest')->group(function () {
    Route::get('/auth/claveunica/redirect', [ClaveUnicaController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('citizen.claveunica.redirect');
    Route::get('/auth/claveunica/callback', [ClaveUnicaController::class, 'callback'])
        ->name('citizen.claveunica.callback');
});

// Simulador local de ClaveUnica. Se exige ademas que el ambiente NO sea
// produccion: una variable de configuracion no puede ser lo unico que separe a
// produccion de un login falsificable. El 12-ago-2026 se detecto que el .env de
// produccion no traia CLAVEUNICA_MODE, caia al default 'mock' del config y
// estas rutas quedaron publicadas en el dominio institucional. Staging
// (APP_ENV=staging) conserva el simulador para QA.
if (! app()->isProduction() && config('claveunica.mode') === 'mock') {
    Route::prefix('dev/claveunica')->group(function () {
        Route::get('/simulate', [MockClaveUnicaController::class, 'simulate'])
            ->name('mock.claveunica.simulate');
        Route::post('/complete', [MockClaveUnicaController::class, 'complete'])
            ->name('mock.claveunica.complete');
    });
}

// Logout del ciudadano autenticado via ClaveUnica. Lo maneja directamente
// el ClaveUnicaController porque ya no hay AuthenticatedCitizenSession.
Route::middleware('auth')->group(function () {
    Route::post('/cerrar-sesion', [ClaveUnicaController::class, 'logout'])
        ->name('citizen.logout');
});

// "Logout URI" declarada ante ClaveUnica: el IdP devuelve aqui por GET despues
// de cerrar su propia sesion. Fuera de los grupos 'auth'/'guest' a proposito —
// llega sin sesion (ya se destruyo en citizen.logout) y debe responder igual si
// alguien la visita directo. Sin abort_unless(enabled): es una URL estatica
// registrada ante un tercero, mejor un redirect al home que un 404.
Route::get('/auth/claveunica/logout', [ClaveUnicaController::class, 'logoutLanding'])
    ->name('citizen.claveunica.logout');

// Backoffice (funcionarios y super-admin)
Route::prefix('admin')
    ->middleware(['auth', 'role:funcionario,super-admin'])
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::resource('consultations', ConsultationController::class)
            ->names('admin.consultations');

        // Restaurar una consulta archivada (soft-deleted). withTrashed para que el
        // route binding resuelva la consulta aunque tenga deleted_at.
        Route::put('consultations/{consultation}/restore', [ConsultationController::class, 'restore'])
            ->withTrashed()
            ->name('admin.consultations.restore');

        // Antecedentes tecnicos (documentos) anidados bajo cada consulta.
        Route::post('consultations/{consultation}/documents',
            [ConsultationDocumentController::class, 'store'])
            ->name('admin.consultations.documents.store');

        Route::get('consultations/{consultation}/documents/{document}/download',
            [ConsultationDocumentController::class, 'download'])
            ->scopeBindings()
            ->name('admin.consultations.documents.download');

        Route::post('consultations/{consultation}/documents/{document}/replace',
            [ConsultationDocumentController::class, 'replace'])
            ->scopeBindings()
            ->name('admin.consultations.documents.replace');

        Route::delete('consultations/{consultation}/documents/{document}',
            [ConsultationDocumentController::class, 'destroy'])
            ->scopeBindings()
            ->name('admin.consultations.documents.destroy');

        // Observaciones recibidas: listado con filtros + export. Disponible
        // para funcionario y super-admin.
        Route::get('observations', [AdminObservationController::class, 'index'])
            ->name('admin.observations.index');
        Route::get('observations/export/{format}', [AdminObservationController::class, 'export'])
            ->whereIn('format', ['xlsx', 'csv'])
            ->name('admin.observations.export');

        // Respuestas institucionales en lote: deben definirse ANTES de la ruta
        // `observations/{observation}` para que 'batch' no matchee como id.
        Route::get('observations/batch', [InstitutionalResponseController::class, 'batchCreate'])
            ->name('admin.observations.batch.create');
        Route::post('observations/batch', [InstitutionalResponseController::class, 'batchStore'])
            ->name('admin.observations.batch.store');

        Route::get('observations/{observation}', [AdminObservationController::class, 'show'])
            ->name('admin.observations.show');
        Route::get('observations/{observation}/attachment',
            [AdminObservationController::class, 'downloadAttachment'])
            ->name('admin.observations.attachment.download');

        // Archivar / restaurar observaciones (papelera): SOLO super-admin. Las
        // observaciones son inalterables en contenido; archivar es reversible.
        Route::middleware('role:super-admin')->group(function () {
            Route::delete('observations/{observation}/archive',
                [AdminObservationController::class, 'archive'])
                ->name('admin.observations.archive');
            Route::put('observations/{observation}/restore',
                [AdminObservationController::class, 'restore'])
                ->withTrashed()
                ->name('admin.observations.restore');
        });

        // Respuesta institucional por observacion individual.
        Route::post('observations/{observation}/response',
            [InstitutionalResponseController::class, 'store'])
            ->name('admin.observations.response.store');
        Route::put('observations/{observation}/response',
            [InstitutionalResponseController::class, 'update'])
            ->name('admin.observations.response.update');
        Route::post('observations/{observation}/response/publish',
            [InstitutionalResponseController::class, 'publish'])
            ->name('admin.observations.response.publish');
        Route::delete('observations/{observation}/response',
            [InstitutionalResponseController::class, 'destroyDraft'])
            ->name('admin.observations.response.destroy');

        // Gestion de funcionarios y super-admin: restringido a super-admin.
        // Los ciudadanos NO se gestionan aqui — se autoregistran via
        // ClaveUnica o flujo manual (Etapa 4).
        Route::middleware('role:super-admin')->group(function () {
            Route::resource('users', UserController::class)
                ->except(['show'])
                ->names('admin.users');

            Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                ->name('admin.users.toggle-active');

            // Bitacora de auditoria (D20) — solo super-admin puede consultarla.
            Route::get('activity-log', [ActivityLogController::class, 'index'])
                ->name('admin.activity-log.index');
        });
    });

require __DIR__.'/auth.php';
