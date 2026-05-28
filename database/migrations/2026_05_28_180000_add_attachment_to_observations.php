<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que el ciudadano adjunte un archivo (PDF/imagen/doc) al enviar
 * una observacion. Todo opcional para no romper el flujo actual.
 *
 * El path apunta al disco s3 (configurado en config/filesystems.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('category');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime_type', 100)->nullable()->after('attachment_original_name');
            $table->unsignedInteger('attachment_size_bytes')->nullable()->after('attachment_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropColumn([
                'attachment_path',
                'attachment_original_name',
                'attachment_mime_type',
                'attachment_size_bytes',
            ]);
        });
    }
};
