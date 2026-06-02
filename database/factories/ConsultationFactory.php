<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);
        $startsAt = fake()->dateTimeBetween('-1 month', 'now');
        $endsAt = (clone $startsAt)->modify('+45 days');

        return [
            'slug' => Str::slug($title) . '-' . fake()->unique()->numerify('####'),
            'title' => $title,
            'summary' => fake()->paragraph(2),
            'description' => fake()->paragraphs(5, true),
            'instrument_type' => fake()->randomElement([
                Consultation::TYPE_IPT,
                Consultation::TYPE_PROT,
                Consultation::TYPE_ZUBC,
            ]),
            'status' => Consultation::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
            'created_by' => User::factory()->functionary(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Consultation::STATUS_DRAFT]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => Consultation::STATUS_ACTIVE]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => Consultation::STATUS_CLOSED,
            'ends_at' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    public function prot(): static
    {
        return $this->state(fn () => ['instrument_type' => Consultation::TYPE_PROT]);
    }

    public function ipt(): static
    {
        return $this->state(fn () => ['instrument_type' => Consultation::TYPE_IPT]);
    }
}
