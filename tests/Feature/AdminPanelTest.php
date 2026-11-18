<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_all_panel_pages_render(): void
    {
        $service = Service::first();
        $plan = Plan::first();
        $promotion = Promotion::first();

        foreach ([
            '/admin', '/admin/servicios', '/admin/servicios/create', "/admin/servicios/{$service->slug}/edit",
            "/admin/servicios/{$service->slug}/planes/create", "/admin/planes/{$plan->id}/edit",
            '/admin/categorias', '/admin/categorias/create', '/admin/promociones', '/admin/promociones/create',
            "/admin/promociones/{$promotion->id}/edit", '/admin/ajustes',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_toggle_service_and_plan_availability(): void
    {
        $service = Service::where('name', 'Netflix')->first();
        $plan = $service->plans->first();

        $this->patch(route('admin.services.toggle', $service))->assertRedirect();
        $this->assertFalse($service->fresh()->is_available);

        $this->patch(route('admin.plans.toggle', $plan))->assertRedirect();
        $this->assertFalse($plan->fresh()->is_available);
    }

    public function test_create_service_with_plan(): void
    {
        $this->post(route('admin.services.store'), [
            'name' => 'Apple TV+',
            'color' => '#000000',
            'features' => "Originales de Apple\n\n  Calidad 4K  ",
            'is_available' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('admin.services.edit', 'apple-tv'));

        $service = Service::where('slug', 'apple-tv')->firstOrFail();
        $this->assertSame(['Originales de Apple', 'Calidad 4K'], $service->features);

        $this->post(route('admin.plans.store', $service), [
            'name' => 'Perfil', 'duration_months' => 1, 'price_usd' => '2.50', 'price_cop' => '', 'is_available' => '1',
        ])->assertRedirect(route('admin.services.edit', $service));

        $this->assertNull($service->plans()->first()->price_cop);
    }

    public function test_promotion_percent_must_be_between_1_and_100(): void
    {
        foreach ([0, 101] as $percent) {
            $this->post(route('admin.promotions.store'), ['name' => 'X', 'percent' => $percent, 'scope' => 'all'])
                ->assertSessionHasErrors('percent');
        }
    }

    public function test_promotion_keeps_only_the_chosen_scope(): void
    {
        $service = Service::first();

        $this->post(route('admin.promotions.store'), [
            'name' => 'Black Friday', 'percent' => 35, 'scope' => 'service',
            'service_id' => $service->id, 'category_id' => $service->category_id, 'is_active' => '1',
        ])->assertRedirect(route('admin.promotions.index'));

        $promo = Promotion::where('name', 'Black Friday')->first();
        $this->assertSame($service->id, $promo->service_id);
        $this->assertNull($promo->category_id);
    }

    public function test_scope_requires_its_target(): void
    {
        $this->post(route('admin.promotions.store'), ['name' => 'X', 'percent' => 10, 'scope' => 'plan'])
            ->assertSessionHasErrors('plan_id');
    }

    public function test_update_settings(): void
    {
        $this->put(route('admin.settings.update'), [
            'store_name' => 'Mi tienda', 'rate_mode' => 'manual', 'usd_to_cop' => '4100', 'default_currency' => 'USD', 'whatsapp' => '+57 300 123 4567',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('4100', Setting::get('usd_to_cop'));
        $this->assertSame('USD', Setting::get('default_currency'));
        $this->assertSame('573001234567', Setting::get('whatsapp'));
    }
}
