<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tras la incorporacion del tipo de actor (acta GORE junio 2026, punto 3),
 * snapshot_full_name solo aplica a actor_type='natural'. Para 'pj' y 'org'
 * el nombre vive en snapshot_legal_name (razon social).
 *
 * La migracion original `create_observations_table` declaro snapshot_full_name
 * como NOT NULL. Lo relajamos para permitir las observaciones de PJ/Org.
 *
 * El invariante "snapshot_full_name requerido para natural" se valida en
 * Observation::creating() y en StoreObservationRequest, no a nivel BD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->string('snapshot_full_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No restauramos NOT NULL: si quedaron filas PJ/Org con
        // snapshot_full_name=null, el ALTER fallaria. El usuario que reverse
        // debe limpiar primero.
        Schema::table('observations', function (Blueprint $table) {
            $table->string('snapshot_full_name')->nullable(false)->change();
        });
    }
};
