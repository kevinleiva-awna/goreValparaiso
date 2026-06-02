<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega attachment_disk a observations para que cada adjunto recuerde
 * con que disk se subio. Solo aplica a filas con attachment_path NOT NULL;
 * las demas pueden quedar con disk null (no hay archivo que ubicar).
 *
 * Backfill: las observaciones existentes con adjunto usaron disk='s3'
 * (era el unico valor hardcoded en ObservationController::store).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->string('attachment_disk', 20)->nullable()->after('attachment_path');
        });

        DB::table('observations')
            ->whereNotNull('attachment_path')
            ->whereNull('attachment_disk')
            ->update(['attachment_disk' => 's3']);
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropColumn('attachment_disk');
        });
    }
};
