<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\ConsultationDocument;
use App\Models\ConsultationStage;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Sin WithoutModelEvents: los hooks 'creating' generan public_id (UUID)
    // automaticamente para Consultation, Observation y file_group_id para Documents.

    public function run(): void
    {
        $this->command->info('Seeding usuarios institucionales...');

        $superAdmin = User::factory()->superAdmin()->create([
            'national_id' => '15123456-7',
            'name' => 'Kevin',
            'last_name' => 'AWNA',
            'email' => 'kevin@awna.cl',
            'password' => Hash::make('password'),
        ]);

        $claudio = User::factory()->functionary()->create([
            'national_id' => '12345678-9',
            'name' => 'Claudio',
            'last_name' => 'UOT',
            'email' => 'claudio@gorevalparaiso.cl',
            'password' => Hash::make('password'),
        ]);

        $gabriel = User::factory()->functionary()->create([
            'national_id' => '13456789-0',
            'name' => 'Gabriel',
            'last_name' => 'San Martin',
            'email' => 'gabriel@gorevalparaiso.cl',
            'password' => Hash::make('password'),
        ]);

        $this->command->info('Seeding ciudadanos de prueba...');
        $citizens = User::factory()->count(5)->citizen()->create();

        $this->command->info('Seeding consulta PROT activa...');
        $prot = Consultation::factory()->prot()->active()->create([
            'slug' => 'prot-valparaiso-2026',
            'title' => 'PROT Region de Valparaiso 2026',
            'summary' => 'Plan Regional de Ordenamiento Territorial para la Region de Valparaiso, periodo 2026-2036.',
            'description' => "Proceso de consulta publica para el Plan Regional de Ordenamiento Territorial (PROT) de la Region de Valparaiso.\n\nEste instrumento orienta el uso del territorio regional considerando criterios ambientales, sociales y economicos, y se elabora en cumplimiento de la Ley N°21.074 sobre fortalecimiento de la regionalizacion.",
            'instrument_type' => Consultation::TYPE_PROT,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(35),
            'created_by' => $claudio->id,
        ]);

        $this->command->info('Seeding etapas de la consulta...');
        $stageDifusion = ConsultationStage::factory()->closed()->informationOnly()->create([
            'consultation_id' => $prot->id,
            'name' => 'Difusion y publicacion',
            'description' => 'Periodo informativo donde se publican antecedentes tecnicos. No se reciben observaciones aun.',
            'position' => 1,
        ]);

        $stageObservaciones = ConsultationStage::factory()->active()->create([
            'consultation_id' => $prot->id,
            'name' => 'Recepcion de observaciones',
            'description' => 'Ventana abierta para que la ciudadania presente observaciones formales al instrumento.',
            'position' => 2,
        ]);

        $this->command->info('Seeding antecedentes tecnicos...');
        ConsultationDocument::factory()->count(3)->create([
            'consultation_id' => $prot->id,
            'stage_id' => $stageDifusion->id,
            'uploaded_by' => $claudio->id,
        ]);

        $this->command->info('Seeding observaciones ciudadanas...');
        // 3 ciudadanos con 1 observacion, 1 ciudadano con 2 (multiples permitidas)
        Observation::factory()->forConsultation($prot, $stageObservaciones)->byUser($citizens[0])->create();
        Observation::factory()->forConsultation($prot, $stageObservaciones)->byUser($citizens[1])->create();
        Observation::factory()->forConsultation($prot, $stageObservaciones)->byUser($citizens[2])->create();
        Observation::factory()->forConsultation($prot, $stageObservaciones)->byUser($citizens[3])->create();
        Observation::factory()->forConsultation($prot, $stageObservaciones)->byUser($citizens[3])->create();

        $this->command->info('Seeding una respuesta institucional ejemplo...');
        $firstObs = Observation::query()->first();
        InstitutionalResponse::create([
            'observation_id' => $firstObs->id,
            'content' => 'Estimado(a), agradecemos su observacion. Sera considerada en la evaluacion tecnica del instrumento.',
            'responded_by' => $claudio->id,
            'responded_at' => now(),
            'status' => InstitutionalResponse::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->command->info('Seeding completado.');
        $this->command->info("Super-admin: {$superAdmin->email} / password");
        $this->command->info("Funcionarios: {$claudio->email}, {$gabriel->email} / password");
        $this->command->info("Ciudadanos: {$citizens->count()} creados");
        $this->command->info("Consulta: {$prot->title} (slug: {$prot->slug})");
    }
}
