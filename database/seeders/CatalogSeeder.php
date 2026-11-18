<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Catálogo de ejemplo para desarrollo. Los precios son ilustrativos:
 * cámbialos desde el panel. Se puede ejecutar varias veces sin duplicar.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put([
            'store_name' => Setting::get('store_name'),
            'usd_to_cop' => Setting::get('usd_to_cop'),
            'default_currency' => Setting::get('default_currency'),
        ]);

        $categories = collect([
            ['Streaming', '🎬'],
            ['Música', '🎧'],
            ['Inteligencia artificial', '🤖'],
            ['Diseño y productividad', '🎨'],
            ['Licencias', '🔑'],
        ])->mapWithKeys(fn ($c, $i) => [$c[0] => Category::updateOrCreate(
            ['name' => $c[0]],
            ['icon' => $c[1], 'sort_order' => $i, 'is_active' => true],
        )]);

        // [categoría, nombre, color, frase, destacado, características, planes [nombre, meses, usd, cop|null]]
        $catalog = [
            ['Streaming', 'Netflix', '#e50914', 'Series y películas en 4K', true,
                ['Calidad 4K UHD', 'Perfil propio con PIN', 'Garantía todo el periodo'],
                [['Perfil individual', 1, 3.99, 15900], ['Perfil individual', 3, 10.99, 43900], ['Perfil individual', 12, 39.99, 159900]]],
            ['Streaming', 'Disney+', '#113ccf', 'Disney, Pixar, Marvel y Star Wars', true,
                ['Calidad 4K', 'Perfil propio', 'Descargas sin conexión'],
                [['Perfil individual', 1, 2.99, null], ['Perfil individual', 6, 15.99, null]]],
            ['Streaming', 'Max', '#002be7', 'HBO, Warner y DC', false,
                ['Estrenos de HBO', 'Perfil propio'],
                [['Perfil individual', 1, 2.99, null], ['Perfil individual', 3, 7.99, null]]],
            ['Streaming', 'Prime Video', '#00a8e1', 'Originales de Amazon', false,
                ['Perfil propio', 'Calidad HD'],
                [['Perfil individual', 1, 2.49, null]]],
            ['Streaming', 'Crunchyroll', '#f47521', 'El mayor catálogo de anime', false,
                ['Sin anuncios', 'Simulcast'],
                [['Cuenta Fan', 1, 1.99, null], ['Cuenta Fan', 12, 17.99, null]]],
            ['Música', 'Spotify', '#1db954', 'Música sin anuncios', true,
                ['Sin anuncios', 'Descargas sin conexión', 'Tu propia cuenta'],
                [['Cuenta individual', 1, 2.99, 11900], ['Cuenta individual', 12, 29.99, 119900]]],
            ['Música', 'YouTube Premium', '#ff0000', 'YouTube y YouTube Music sin anuncios', true,
                ['Sin anuncios', 'Reproducción en segundo plano', 'YouTube Music incluido'],
                [['Invitación familiar', 1, 2.49, null], ['Invitación familiar', 12, 24.99, null]]],
            ['Inteligencia artificial', 'ChatGPT Plus', '#10a37f', 'Los modelos más avanzados de OpenAI', true,
                ['Acceso compartido', 'Modelos avanzados', 'Soporte por WhatsApp'],
                [['Cupo compartido', 1, 6.99, 27900]]],
            ['Diseño y productividad', 'Canva Pro', '#00c4cc', 'Diseño profesional sin límites', false,
                ['Plantillas premium', 'Quitar fondos', 'Kit de marca'],
                [['Invitación a equipo', 12, 9.99, 39900]]],
            ['Licencias', 'Autodesk', '#0696d7', 'AutoCAD, Revit, 3ds Max y más', false,
                ['Licencia educativa', 'Todas las apps de Autodesk', 'Activación en tu correo'],
                [['Licencia All Apps', 12, 14.99, null]]],
            ['Licencias', 'Microsoft 365', '#0078d4', 'Word, Excel, PowerPoint y 1 TB en OneDrive', false,
                ['Apps de Office de escritorio', '1 TB en OneDrive', 'Hasta 5 dispositivos'],
                [['Cuenta individual', 12, 9.99, null]]],
            ['Licencias', 'Adobe Creative Cloud', '#da1f26', 'Photoshop, Illustrator, Premiere y más', true,
                ['Más de 20 apps de Adobe', 'Adobe Firefly incluido', '100 GB en la nube'],
                [['Todas las apps', 1, 9.99, null], ['Todas las apps', 12, 79.99, null]]],
        ];

        foreach ($catalog as $i => [$category, $name, $color, $tagline, $featured, $features, $plans]) {
            $service = Service::updateOrCreate(['name' => $name], [
                'category_id' => $categories[$category]->id,
                'color' => $color,
                'tagline' => $tagline,
                'features' => $features,
                'is_featured' => $featured,
                'is_active' => true,
                'is_available' => $name !== 'Max',   // uno agotado para ver el estado
                'sort_order' => $i,
            ]);

            // Logo incluido en public/images/services, sin pisar uno subido desde el panel.
            $logo = 'images/services/'.$service->slug.'.svg';
            if (blank($service->logo) && file_exists(public_path($logo))) {
                $service->update(['logo' => $logo]);
            }

            foreach ($plans as $j => [$planName, $months, $usd, $cop]) {
                $service->plans()->updateOrCreate(
                    ['name' => $planName, 'duration_months' => $months],
                    ['price_usd' => $usd, 'price_cop' => $cop, 'is_available' => true, 'sort_order' => $j],
                );
            }
        }

        Promotion::updateOrCreate(['name' => 'Bienvenida Spotify'], [
            'percent' => 20,
            'service_id' => Service::where('name', 'Spotify')->value('id'),
            'is_active' => true,
        ]);
    }
}
