<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina el concepto de "etapas" del proceso de consulta (junio 2026).
 *
 * Las observaciones y los antecedentes pasan a colgar directamente de la
 * consulta; la ventana de participacion la define la propia consulta
 * (status + starts_at/ends_at), sin una etapa intermedia.
 *
 * DESTRUCTIVA: borra la tabla consultation_stages y la columna stage_id de
 * observations y consultation_documents. Tomar snapshot de RDS antes de
 * desplegar a preprod (los datos de etapas y stage_id no se recuperan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            // El indice compuesto referencia stage_id: hay que soltarlo antes
            // de poder dropear la columna (MariaDB).
            $table->dropIndex(['consultation_id', 'stage_id']);
            // Suelta la FK y la columna en un paso.
            $table->dropConstrainedForeignId('stage_id');
            // Repone un indice util para los listados/filtros por proceso.
            $table->index('consultation_id');
        });

        Schema::table('consultation_documents', function (Blueprint $table) {
            $table->dropIndex(['consultation_id', 'stage_id']);
            $table->dropConstrainedForeignId('stage_id');
            $table->index('consultation_id');
        });

        Schema::dropIfExists('consultation_stages');
    }

    /**
     * Reversion best-effort: recrea la estructura para que migrate:rollback no
     * falle, pero NO restaura datos. stage_id vuelve como NULLABLE para no
     * romper filas existentes que ya no tienen una etapa a la cual apuntar.
     */
    public function down(): void
    {
        Schema::create('consultation_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('accepts_observations')->default(true);
            $table->enum('status', ['pending', 'active', 'closed'])->default('pending');
            $table->timestamps();
            $table->unique(['consultation_id', 'position']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->dropIndex(['consultation_id']);
            $table->foreignId('stage_id')->nullable()->after('consultation_id')
                ->constrained('consultation_stages')->nullOnDelete();
            $table->index(['consultation_id', 'stage_id']);
        });

        Schema::table('consultation_documents', function (Blueprint $table) {
            $table->dropIndex(['consultation_id']);
            $table->foreignId('stage_id')->nullable()->after('consultation_id')
                ->constrained('consultation_stages')->nullOnDelete();
            $table->index(['consultation_id', 'stage_id']);
        });
    }
};
