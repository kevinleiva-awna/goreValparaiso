<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Acta de Observaciones GORE (junio 2026), punto 2: eliminacion total del
 * registro manual (email+password). Solo quedan dos caminos: ClaveUnica
 * y participacion sin registro (guest).
 *
 * Esta migracion limpia el dato existente:
 *  - Observaciones con auth_method_used='manual' Y snapshot_national_id no nulo
 *    -> se reclasifican como 'claveunica' (el usuario igual entrego RUT).
 *  - Observaciones con auth_method_used='manual' Y snapshot_national_id nulo
 *    -> se reclasifican como 'guest'.
 *  - Consultas con 'manual' en auth_methods (JSON) -> se remueve.
 *
 * Y reduce el ENUM a ('claveunica','guest'). Esto bloqueara INSERTs futuros
 * con 'manual'.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1) Backfill observaciones existentes con auth_method_used='manual'
        DB::table('observations')
            ->where('auth_method_used', 'manual')
            ->whereNotNull('snapshot_national_id')
            ->update(['auth_method_used' => 'claveunica']);

        DB::table('observations')
            ->where('auth_method_used', 'manual')
            ->whereNull('snapshot_national_id')
            ->update(['auth_method_used' => 'guest']);

        // 2) Reducir enum (solo MySQL/MariaDB; SQLite usa TEXT+CHECK y tests
        //    no usan 'manual' a partir de este sprint).
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE observations
                 MODIFY auth_method_used ENUM('claveunica','guest') NOT NULL"
            );
        }

        // 3) Limpiar 'manual' del array auth_methods de cada consulta.
        //    Iteramos en PHP en vez de SQL para no acoplarnos a sintaxis JSON
        //    especifica de MariaDB/MySQL.
        DB::table('consultations')->orderBy('id')->each(function ($row) {
            $methods = json_decode($row->auth_methods ?? '[]', true) ?: [];
            $filtered = array_values(array_filter($methods, fn ($m) => $m !== 'manual'));
            // Si la consulta queda sin metodos -> agregar 'claveunica' como
            // minimo viable. Evita dejar consultas inutilizables.
            if (empty($filtered)) {
                $filtered = ['claveunica'];
            }
            if ($filtered !== $methods) {
                DB::table('consultations')
                    ->where('id', $row->id)
                    ->update(['auth_methods' => json_encode($filtered)]);
            }
        });
    }

    public function down(): void
    {
        // Restaurar enum con 'manual' de vuelta. NO restauramos las filas
        // backfilleadas (la informacion del metodo original se perdio):
        // las observaciones quedaran etiquetadas como 'claveunica' o 'guest'
        // segun el backfill, lo cual es el estado correcto post-decision.
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE observations
                 MODIFY auth_method_used ENUM('claveunica','manual','guest') NOT NULL"
            );
        }
    }
};
