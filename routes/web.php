<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ConsultationDocumentController;
use App\Http\Controllers\Admin\ConsultationStageController;
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
    // Las 3 consultas mas recientes en estado activo o publicado para el hero.
    $featured = Consultation::query()
        ->whereIn('status', [Consultation::STATUS_ACTIVE, Consultation::STATUS_PUBLISHED])
        ->withCount('observations')
        ->orderByDesc('starts_at')
        ->limit(3)
        ->get();

    // Stats agregados para el hero (acta junio 2026, punto 1: mas llamativo).
    $stats = [
        'active_processes' => Consultation::query()
            ->where('status', Consultation::STATUS_ACTIVE)
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

    Route::get('/{slug}/observaciones/{publicId}/exito',
        [PublicObservationController::class, 'success'])
        ->middleware('auth')
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

// Simulador local de ClaveUnica. Solo se registra cuando config('claveunica.mode')
// es 'mock'. En produccion estas rutas NO existen — el provider real las reemplaza.
if (config('claveunica.mode') === 'mock') {
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

        // Etapas anidadas bajo cada consulta. scoped() valida que el stage_id
        // pertenezca a la consultation_id de la URL.
        Route::resource('consultations.stages', ConsultationStageController::class)
            ->scoped()
            ->except(['index', 'show'])
            ->names('admin.consultations.stages');

        Route::post('consultations/{consultation}/stages/{stage}/move/{direction}',
            [ConsultationStageController::class, 'move'])
            ->scopeBindings()
            ->whereIn('direction', ['up', 'down'])
            ->name('admin.consultations.stages.move');

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
