<?php

namespace Database\Factories;

use App\Models\Combo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Combo>
 */
class ComboFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Combo '.fake()->unique()->word(),
            'description' => '1 pantalla de cada plataforma por 30 días.',
            'duration_months' => 1,
            'price_usd' => 4.05,
            'is_active' => true,
            'is_available' => true,
        ];
    }
}
