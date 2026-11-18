<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Combos de ejemplo armados con los planes de 1 mes del catálogo.
 * Se puede ejecutar varias veces sin duplicar.
 */
class ComboSeeder extends Seeder
{
    public function run(): void
    {
        // [nombre, descripción, precio USD, más popular, servicios (slug)]
        $combos = [
            ['Combo Diamante', '1 pantalla de cada plataforma por 30 días.', 4.05, false, ['disney', 'crunchyroll']],
            ['Combo Oferta', '1 pantalla de cada una por 30 días.', 5.99, false, ['netflix', 'disney']],
            ['Combo Full Top', '1 dispositivo de cada plataforma por 30 días.', 4.45, true, ['youtube-premium', 'spotify']],
        ];

        foreach ($combos as $i => [$name, $description, $priceUsd, $featured, $slugs]) {
            $plans = Plan::query()
                ->where('duration_months', 1)
                ->whereHas('service', fn ($query) => $query->whereIn('slug', $slugs))
                ->get()
                ->unique('service_id');

            if ($plans->count() < 2) {
                continue;
            }

            $combo = Combo::updateOrCreate(['name' => $name], [
                'description' => $description,
                'duration_months' => 1,
                'price_usd' => $priceUsd,
                'is_featured' => $featured,
                'is_active' => true,
                'is_available' => true,
                'sort_order' => $i,
            ]);

            $combo->plans()->sync($plans->pluck('id'));
        }
    }
}
