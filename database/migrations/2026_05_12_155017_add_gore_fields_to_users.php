<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // El RUN (ClaveUnica) y el RUT (flujo manual) son el mismo numero
            // para personas naturales en Chile. Lo normalizamos a un solo campo
            // y registramos por observacion el metodo de autenticacion usado.
            $table->string('national_id', 12)->nullable()->unique()->after('id');

            $table->string('last_name', 100)->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('email');

            $table->enum('role', ['ciudadano', 'funcionario', 'super-admin'])
                ->default('ciudadano')
                ->after('password');

            $table->boolean('is_active')->default(true)->after('role');

            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // Indices para listados y filtros del backoffice
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'national_id',
                'last_name',
                'phone',
                'role',
                'is_active',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
