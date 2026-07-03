<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete en observaciones para permitir "archivar" (papelera + restaurar)
 * desde el backoffice sin destruir el expediente. Las observaciones siguen
 * siendo inalterables en cuanto a su contenido; archivar solo las oculta del
 * listado/export y es reversible, restringido a super-admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
