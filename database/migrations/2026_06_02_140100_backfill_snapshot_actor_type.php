<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pareja de add_actor_fields_to_observations. Backfillea snapshot_actor_type
 * para observaciones pre-existentes y aplica NOT NULL.
 *
 * Separada en migracion propia para no bloquear con copy-table-lock en
 * MariaDB 10.4 cuando hay volumen real (la tabla observations puede crecer
 * a millones segun el acta: "alto numero de observaciones por proceso").
 *
 * Regla de backfill:
 *  - Toda observacion existente queda como 'natural' (era el unico modelo
 *    de actor en el sistema antes del acta de junio 2026). snapshot_id_type
 *    se asume 'rut' porque ClaveUnica solo entrega RUT y los guests previos
 *    al rediseno tambien entregaban RUT.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Backfill. Toda obs existente es 'natural' (el viejo modelo).
        DB::table('observations')
            ->whereNull('snapshot_actor_type')
            ->update(['snapshot_actor_type' => 'natural']);

        // Tambien backfilleamos snapshot_id_type para coherencia. Solo para
        // las que tienen national_id, porque sin national_id no aplica id_type.
        DB::table('observations')
            ->whereNull('snapshot_id_type')
            ->whereNotNull('snapshot_national_id')
            ->update(['snapshot_id_type' => 'rut']);

        // Aplicar NOT NULL a actor_type. Sin default: nuevas filas DEBEN
        // declarar explicitamente el tipo en el controller.
        Schema::table('observations', function (Blueprint $table) {
            $table->string('snapshot_actor_type', 10)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->string('snapshot_actor_type', 10)->nullable()->change();
        });
    }
};
