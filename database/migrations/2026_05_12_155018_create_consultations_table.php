<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('slug', 191)->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();

            // Tipo de instrumento de planificacion: IPT, PROT, ZUBC u otro segun brief
            $table->enum('instrument_type', ['IPT', 'PROT', 'ZUBC', 'OTRO'])->default('IPT');

            // Estados del proceso completo
            $table->enum('status', ['draft', 'published', 'active', 'closed', 'archived'])
                ->default('draft');

            // Ventana global del proceso (las etapas tienen su propia ventana)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Metodos de autenticacion habilitados para ESTE proceso
            // (JSON con valores 'claveunica' y/o 'manual')
            $table->json('auth_methods')->nullable();

            // Cartografia opcional
            $table->string('map_image_url')->nullable();
            $table->json('map_geojson')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'instrument_type']);
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
