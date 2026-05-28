<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Habilita el modo "guest" en observaciones:
 *
 * - user_id pasa a nullable: cuando una consulta admite participacion sin
 *   registro, no hay User detras del comentario.
 * - snapshot_national_id pasa a nullable: el guest no entrega RUT (queda
 *   identificado solo por nombre + email auto-declarados).
 * - auth_method_used acepta 'guest' como tercer valor del enum (solo en
 *   MySQL/MariaDB; SQLite traduce enum a TEXT+CHECK y los tests no usan
 *   'guest' hoy, asi que no necesitamos recrear la tabla alli).
 *
 * No tocamos snapshot_full_name / snapshot_email: en guest mode el ciudadano
 * los completa al enviar, asi que la columna sigue siendo obligatoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('snapshot_national_id', 12)->nullable()->change();
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE observations
                 MODIFY auth_method_used ENUM('claveunica','manual','guest') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // Si hay filas con 'guest', no se puede volver. Las dejamos para
            // que el operador decida que hacer manualmente.
            DB::statement(
                "ALTER TABLE observations
                 MODIFY auth_method_used ENUM('claveunica','manual') NOT NULL"
            );
        }

        Schema::table('observations', function (Blueprint $table) {
            $table->string('snapshot_national_id', 12)->nullable(false)->change();
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
