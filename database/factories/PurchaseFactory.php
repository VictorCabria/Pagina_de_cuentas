<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => 'Cuenta '.fake()->numberBetween(1, 99),
            'amount' => 20000,
            'currency' => Currency::COP,
            'purchased_on' => today(),
        ];
    }
}
