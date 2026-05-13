<?php

use App\Http\Controllers\Admin\ConsultationController;
use App\Http\Controllers\Admin\ConsultationDocumentController;
use App\Http\Controllers\Admin\ConsultationStageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

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
