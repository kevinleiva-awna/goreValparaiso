<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'national_id' => self::randomChileanRut(),
            'name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+569' . fake()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_CITIZEN,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function citizen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_CITIZEN,
        ]);
    }

    public function functionary(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_FUNCTIONARY,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Genera un RUT chileno valido (modulo 11) en formato "12345678-9".
     */
    public static function randomChileanRut(): string
    {
        $base = fake()->numberBetween(5_000_000, 25_000_000);

        return $base . '-' . self::computeDv($base);
    }

    private static function computeDv(int $number): string
    {
        $digits = (string) $number;
        $sum = 0;
        $factor = 2;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += (int) $digits[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $remainder = $sum % 11;
        $dv = 11 - $remainder;

        return match (true) {
            $dv === 11 => '0',
            $dv === 10 => 'K',
            default => (string) $dv,
        };
    }
}
