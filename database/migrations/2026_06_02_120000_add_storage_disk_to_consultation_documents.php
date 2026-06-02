<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega storage_disk a consultation_documents para que cada archivo
 * recuerde con que disk se subio. Permite migrar de 'local' a 's3' sin
 * perder referencia a archivos historicos.
 *
 * Se aplica en dos fases: primero nullable + backfill 'local' (porque
 * el codigo previo a esta serie de cambios siempre uso disk='local'),
 * luego NOT NULL DEFAULT del disk actual via env.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_documents', function (Blueprint $table) {
            $table->string('storage_disk', 20)->nullable()->after('storage_path');
        });

        // Backfill: los documentos pre-existentes se subieron con 'local'
        // (era el unico valor hardcoded en el controller).
        DB::table('consultation_documents')
            ->whereNull('storage_disk')
            ->update(['storage_disk' => 'local']);

        Schema::table('consultation_documents', function (Blueprint $table) {
            $table->string('storage_disk', 20)
                ->default(config('filesystems.default'))
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('consultation_documents', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });
    }
};
