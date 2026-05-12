<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();

            // Denormalizamos consultation_id en la observacion para acelerar
            // los listados/exports del backoffice sin tener que joinear stages.
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('stage_id')->constrained('consultation_stages')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            // Asunto opcional, cuerpo obligatorio
            $table->string('subject')->nullable();
            $table->longText('body');

            // Categoria libre, util para clasificar despues
            $table->string('category', 100)->nullable();

            // Metodo de autenticacion usado AL momento de enviar esta observacion.
            // No es derivable del usuario porque un mismo usuario puede mezclar
            // metodos en distintas observaciones.
            $table->enum('auth_method_used', ['claveunica', 'manual']);

            // Snapshot de identidad al momento de envio (auditoria inalterable
            // si el usuario edita su perfil despues)
            $table->string('snapshot_national_id', 12);
            $table->string('snapshot_full_name');
            $table->string('snapshot_email');

            // Trazabilidad
            $table->timestamp('submitted_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['consultation_id', 'stage_id']);
            $table->index('user_id');
            $table->index('submitted_at');
            $table->index('snapshot_national_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
