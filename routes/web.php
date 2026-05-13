<?php

use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ConsultationDocumentController;
use App\Http\Controllers\Admin\ConsultationStageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\Auth\AuthenticatedCitizenSessionController;
use App\Http\Controllers\Public\Auth\EmailVerificationController;
use App\Http\Controllers\Public\Auth\RegisteredCitizenController;
use App\Http\Controllers\Public\ConsultationController as PublicConsultationController;
use App\Models\Consultation;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Las 3 consultas mas recientes en estado activo o publicado para el hero.
    $featured = Consultation::query()
        ->whereIn('status', [Consultation::STATUS_ACTIVE, Consultation::STATUS_PUBLISHED])
        ->withCount('observations')
        ->orderByDesc('starts_at')
        ->limit(3)
        ->get();

    return view('welcome', ['featured' => $featured]);
})->name('home');

// Portal Ciudadano (publico, sin auth)
Route::prefix('consultas')->group(function () {
    Route::get('/', [PublicConsultationController::class, 'index'])->name('public.consultations.index');
    Route::get('/{slug}', [PublicConsultationController::class, 'show'])->name('public.consultations.show');
    Route::get('/{slug}/antecedentes/{fileGroupId}/descargar',
        [PublicConsultationController::class, 'download'])
        ->name('public.consultations.documents.download');
});

// Auth ciudadana: registro manual con verificacion por correo obligatoria.
// Esta separada de /admin/login que sigue siendo solo para staff.
Route::middleware('guest')->group(function () {
    Route::get('/registrarme', [RegisteredCitizenController::class, 'create'])
        ->name('citizen.register');
    Route::post('/registrarme', [RegisteredCitizenController::class, 'store'])
        ->name('citizen.register.store');

    Route::get('/ingresar', [AuthenticatedCitizenSessionController::class, 'create'])
        ->name('citizen.login');
    Route::post('/ingresar', [AuthenticatedCitizenSessionController::class, 'store'])
        ->name('citizen.login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/cerrar-sesion', [AuthenticatedCitizenSessionController::class, 'destroy'])
        ->name('citizen.logout');

    // Verificacion de email del ciudadano
    Route::get('/email/verificar', [EmailVerificationController::class, 'notice'])
        ->name('citizen.verification.notice');

    Route::get('/email/verificar/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('citizen.verification.verify');

    Route::post('/email/reenviar-verificacion', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('citizen.verification.resend');
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

        // Gestion de funcionarios y super-admin: restringido a super-admin.
        // Los ciudadanos NO se gestionan aqui — se autoregistran via
        // ClaveUnica o flujo manual (Etapa 4).
        Route::middleware('role:super-admin')->group(function () {
            Route::resource('users', UserController::class)
                ->except(['show'])
                ->names('admin.users');

            Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                ->name('admin.users.toggle-active');
        });
    });

require __DIR__.'/auth.php';
