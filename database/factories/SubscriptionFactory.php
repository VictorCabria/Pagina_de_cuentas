<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => fn () => Service::create(['name' => 'Servicio '.fake()->unique()->word()])->id,
            'customer_name' => fake()->firstName(),
            'customer_phone' => '57300'.fake()->numerify('#######'),
            'account_email' => fake()->safeEmail(),
            'account_password' => 'clave-'.fake()->numerify('####'),
            'profile_name' => 'Perfil '.fake()->numberBetween(1, 5),
            'pin' => fake()->numerify('####'),
            'starts_on' => today(),
            'expires_on' => today()->addMonth(),
        ];
    }

    public function withoutAccount(): static
    {
        return $this->state(['account_email' => null, 'account_password' => null, 'profile_name' => null, 'pin' => null]);
    }
}
