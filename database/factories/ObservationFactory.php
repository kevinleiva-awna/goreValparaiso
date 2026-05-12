<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    public function definition(): array
    {
        // Referencias lazy: Laravel sólo las resuelve si NO son sobrescritas por estado.
        // Esto evita crear filas huérfanas cuando forConsultation()/byUser() las anulan.
        return [
            'consultation_id' => Consultation::factory(),
            'stage_id' => ConsultationStage::factory(),
            'user_id' => User::factory()->citizen(),

            'subject' => fake()->sentence(6),
            'body' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement([
                'Uso de suelo',
                'Vialidad',
                'Areas verdes',
                'Patrimonio',
                'Equipamiento',
                'Riesgo natural',
            ]),
            'auth_method_used' => fake()->randomElement([
                Observation::AUTH_CLAVEUNICA,
                Observation::AUTH_MANUAL,
            ]),

            // Closures: se evalúan al momento de persistir, cuando user_id ya es ID real.
            'snapshot_national_id' => fn (array $attrs) => User::find($attrs['user_id'])?->national_id,
            'snapshot_full_name' => function (array $attrs) {
                $u = User::find($attrs['user_id']);
                return $u ? trim($u->name . ' ' . $u->last_name) : '';
            },
            'snapshot_email' => fn (array $attrs) => User::find($attrs['user_id'])?->email,

            'submitted_at' => fake()->dateTimeBetween('-15 days', 'now'),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function forConsultation(Consultation $consultation, ?ConsultationStage $stage = null): static
    {
        return $this->state(function () use ($consultation, $stage) {
            $stage = $stage ?? $consultation->stages()
                ->where('accepts_observations', true)
                ->where('status', ConsultationStage::STATUS_ACTIVE)
                ->firstOrFail();

            return [
                'consultation_id' => $consultation->id,
                'stage_id' => $stage->id,
            ];
        });
    }

    public function byUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'snapshot_national_id' => $user->national_id,
            'snapshot_full_name' => trim($user->name . ' ' . $user->last_name),
            'snapshot_email' => $user->email,
        ]);
    }
}
