<?php

namespace Database\Factories;

use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionalResponse>
 */
class InstitutionalResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'observation_id' => Observation::factory(),
            'content' => fake()->paragraphs(2, true),
            'batch_id' => null,
            'responded_by' => User::factory()->functionary(),
            'responded_at' => now(),
            'status' => InstitutionalResponse::STATUS_DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => InstitutionalResponse::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function forBatch(string $batchId): static
    {
        return $this->state(fn () => ['batch_id' => $batchId]);
    }
}
