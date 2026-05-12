<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Orden secuencial dentro del proceso (1, 2, 3...)
            $table->unsignedSmallInteger('position')->default(1);

            // Nullable a nivel DB (validacion required a nivel FormRequest);
            // evita problema de 'invalid default value' en MariaDB con SQL strict.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Algunas etapas son solo informativas (difusion); otras aceptan observaciones
            $table->boolean('accepts_observations')->default(true);

            // Estado individual de la etapa (puede diferir del status del proceso global)
            $table->enum('status', ['pending', 'active', 'closed'])->default('pending');

            $table->timestamps();

            $table->unique(['consultation_id', 'position']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_stages');
    }
};
