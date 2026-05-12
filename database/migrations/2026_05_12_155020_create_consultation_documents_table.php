<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();

            // Un documento puede estar a nivel proceso (consultation) o atado
            // a una etapa especifica del proceso. Si stage_id es null, aplica al proceso.
            $table->foreignId('stage_id')
                ->nullable()
                ->constrained('consultation_stages')
                ->nullOnDelete();

            $table->string('title');
            $table->string('description')->nullable();

            $table->string('original_filename');
            $table->string('mime_type', 191);
            $table->unsignedBigInteger('size_bytes');

            // Path en disco (S3 en prod, local en dev). Driver lo decide.
            $table->string('storage_path');

            // Versionado: si se reemplaza el archivo, se crea una nueva fila
            // que apunta al mismo grupo (file_group_id) con version incrementada.
            $table->uuid('file_group_id');
            $table->unsignedInteger('version')->default(1);

            // Para hashear y verificar integridad
            $table->string('sha256', 64)->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['consultation_id', 'stage_id']);
            $table->index('file_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_documents');
    }
};
