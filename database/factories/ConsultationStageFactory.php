<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationStage>
 */
class ConsultationStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'name' => fake()->randomElement([
                'Difusion y publicacion',
                'Recepcion de observaciones',
                'Analisis y respuestas',
            ]),
            'description' => fake()->paragraph(),
            'position' => 1,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(30),
            'accepts_observations' => true,
            'status' => ConsultationStage::STATUS_ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ConsultationStage::STATUS_ACTIVE,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(20),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ConsultationStage::STATUS_CLOSED,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->subDays(5),
        ]);
    }

    public function informationOnly(): static
    {
        return $this->state(fn () => [
            'accepts_observations' => false,
        ]);
    }
}
