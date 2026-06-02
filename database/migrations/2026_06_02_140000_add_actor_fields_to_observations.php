<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Acta de Observaciones GORE (junio 2026), punto 3: agregar diferenciacion
 * por tipo de actor antes del formulario de observacion.
 *
 *  - 'natural' (Persona Natural sin ClaveUnica): nombre, email, tipo identif
 *    (RUT/PASAPORTE), N°. Opcionales: telefono, comuna, edad.
 *  - 'pj' (Persona Juridica): razon social, email, RUT. Opcionales:
 *    nombre fantasia, telefono, direccion.
 *  - 'org' (Organizacion sin PJ): igual que PJ.
 *
 * NO tocamos User: ClaveUnica solo identifica personas naturales chilenas,
 * asi que la diferenciacion vive en el snapshot de cada observacion.
 *
 * Esta migracion deja las columnas nullable. Una segunda migracion separada
 * hace backfill y aplica NOT NULL al snapshot_actor_type (evita copy-table
 * lock prolongado en MariaDB 10.4 si la tabla crece a millones).
 *
 * Uso VARCHAR + CHECK (no ENUM) por la misma razon: agregar valores futuros
 * (ej. 'org_publica') no requiere ALTER TABLE con lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            // Tipo de actor (PN/PJ/Org). Nullable en esta primer migracion,
            // backfill + NOT NULL en la migracion separada.
            $table->string('snapshot_actor_type', 10)->nullable()->after('user_id');

            // Solo aplica a 'natural': tipo de identificacion entregada.
            // ClaveUnica siempre 'rut' (Chile); guest puede declarar 'rut'
            // o 'pasaporte' (residentes extranjeros).
            $table->string('snapshot_id_type', 10)->nullable()->after('snapshot_actor_type');

            // PJ / Org: razon social (obligatorio) y nombre fantasia (opcional).
            $table->string('snapshot_legal_name', 200)->nullable()->after('snapshot_full_name');
            $table->string('snapshot_trade_name', 200)->nullable()->after('snapshot_legal_name');

            // PJ / Org: RUT de la empresa/organizacion. Distinto del RUT del
            // representante (que iria en snapshot_national_id si entrara via
            // ClaveUnica, pero PJ/Org NO usan ClaveUnica en este modelo).
            $table->string('snapshot_business_id', 12)->nullable()->after('snapshot_national_id');

            // Comunes opcionales.
            $table->string('snapshot_phone', 20)->nullable()->after('snapshot_business_id');
            $table->string('snapshot_address', 255)->nullable()->after('snapshot_phone');
            $table->string('snapshot_comuna', 100)->nullable()->after('snapshot_address');
            $table->unsignedTinyInteger('snapshot_age')->nullable()->after('snapshot_comuna');

            // Indices para filtros del admin (acta punto 6).
            $table->index(['snapshot_actor_type', 'consultation_id'], 'idx_obs_actor_consult');
            $table->index('snapshot_business_id');
        });

        // CHECK constraints. Solo MySQL >= 8 y MariaDB >= 10.2 los soportan
        // realmente; en SQLite van como constraints normales.
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE observations
                 ADD CONSTRAINT chk_obs_actor_type
                 CHECK (snapshot_actor_type IS NULL OR snapshot_actor_type IN ('natural','pj','org'))"
            );
            DB::statement(
                "ALTER TABLE observations
                 ADD CONSTRAINT chk_obs_id_type
                 CHECK (snapshot_id_type IS NULL OR snapshot_id_type IN ('rut','pasaporte'))"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE observations DROP CONSTRAINT IF EXISTS chk_obs_actor_type');
            DB::statement('ALTER TABLE observations DROP CONSTRAINT IF EXISTS chk_obs_id_type');
        }

        Schema::table('observations', function (Blueprint $table) {
            $table->dropIndex('idx_obs_actor_consult');
            $table->dropIndex(['snapshot_business_id']);
            $table->dropColumn([
                'snapshot_actor_type',
                'snapshot_id_type',
                'snapshot_legal_name',
                'snapshot_trade_name',
                'snapshot_business_id',
                'snapshot_phone',
                'snapshot_address',
                'snapshot_comuna',
                'snapshot_age',
            ]);
        });
    }
};
