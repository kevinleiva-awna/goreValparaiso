<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationDocument>
 */
class ConsultationDocumentFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->randomElement([
            'antecedentes-tecnicos.pdf',
            'memoria-explicativa.pdf',
            'plano-instrumento.pdf',
            'estudio-impacto.pdf',
            'informe-ambiental.pdf',
        ]);

        return [
            'consultation_id' => Consultation::factory(),
            'stage_id' => null,
            'title' => ucfirst(str_replace(['-', '.pdf'], [' ', ''], $filename)),
            'description' => fake()->sentence(),
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(50_000, 5_000_000),
            'storage_path' => 'consultations/documents/' . fake()->uuid() . '.pdf',
            'version' => 1,
            'sha256' => hash('sha256', fake()->uuid()),
            'uploaded_by' => User::factory()->functionary(),
        ];
    }
}
