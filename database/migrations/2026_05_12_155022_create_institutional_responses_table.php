<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->constrained()->restrictOnDelete();

            $table->longText('content');

            // Cuando la respuesta es parte de un lote (varias observaciones
            // respondidas con el mismo texto), comparten batch_id.
            $table->uuid('batch_id')->nullable();

            $table->foreignId('responded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('responded_at')->useCurrent();

            // Estado de publicacion: borrador del funcionario vs visible al ciudadano
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index('batch_id');
            $table->index(['status', 'published_at']);

            // Una observacion puede tener UNA sola respuesta institucional vigente.
            // (Si se reemplaza, se borra la anterior o se versiona aparte.)
            $table->unique('observation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_responses');
    }
};
