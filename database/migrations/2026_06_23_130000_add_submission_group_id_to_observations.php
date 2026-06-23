<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrupa las observaciones enviadas en una misma participacion (junio 2026).
 *
 * Un ciudadano puede dejar varias observaciones de distintos temas en un solo
 * envio; todas comparten este submission_group_id (UUID) para mostrarlas juntas
 * en la confirmacion y agruparlas en el backoffice. Nullable: las filas
 * historicas (un envio = una observacion) quedan sin grupo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->uuid('submission_group_id')->nullable()->after('consultation_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropIndex(['submission_group_id']);
            $table->dropColumn('submission_group_id');
        });
    }
};
