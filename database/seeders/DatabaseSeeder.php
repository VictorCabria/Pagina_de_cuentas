<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Usuario administrador y catálogo de ejemplo.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tienda.test')],
            ['name' => 'Administrador', 'password' => env('ADMIN_PASSWORD', 'password')],
        );

        $this->call([CatalogSeeder::class, ComboSeeder::class]);
    }
}
