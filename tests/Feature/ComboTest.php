<?php

namespace Tests\Feature;

use App\Models\Combo;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboTest extends TestCase
{
    use RefreshDatabase;

    private Combo $combo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4000', 'default_currency' => 'COP', 'whatsapp' => '573506241755']);

        // Netflix 1 mes ($15.900 fijo) + Spotify 1 mes ($11.900 fijo) = $27.800 por separado
        $this->combo = Combo::factory()->create(['name' => 'Combo Diamante', 'price_cop' => 22900]);
        $this->combo->plans()->attach([$this->monthlyPlan('netflix')->id, $this->monthlyPlan('spotify')->id]);
    }

    private function monthlyPlan(string $slug): Plan
    {
        return Service::where('slug', $slug)->firstOrFail()->plans()->where('duration_months', 1)->firstOrFail();
    }

    public function test_home_shows_combos_with_what_they_include_and_the_savings(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Planes combo')
            ->assertSeeInOrder(['Combo Diamante', 'Netflix', 'Spotify', '$27.800', '$22.900', 'Ahorras $4.900']);
    }

    public function test_hidden_combo_is_not_shown(): void
    {
        $this->combo->update(['is_active' => false]);

        $this->get(route('home'))->assertDontSee('Combo Diamante');
    }

    public function test_combo_is_sold_out_when_an_included_plan_is_sold_out(): void
    {
        $this->monthlyPlan('spotify')->update(['is_available' => false]);

        $this->post(route('cart.store'), ['combo_id' => $this->combo->id])
            ->assertSessionHas('cart_error', 'Ese combo está agotado en este momento.');

        $this->get(route('home'))->assertSeeInOrder(['Combo Diamante', 'Sin cupos por ahora']);
    }

    public function test_combo_goes_through_the_cart_into_the_order(): void
    {
        $this->post(route('cart.store'), ['combo_id' => $this->combo->id])
            ->assertSessionHas('cart_added', 'Combo Diamante se agregó al carrito.');
        $this->post(route('cart.store'), ['plan_id' => $this->monthlyPlan('netflix')->id]);

        $this->get(route('cart.show'))->assertSeeInOrder(['Combo Diamante', 'Incluye Netflix y Spotify', '$22.900', 'Total', '$38.800']);

        $this->patchJson(route('cart.update', $this->combo->cartKey()), ['quantity' => 2])
            ->assertJson(['quantity' => 2, 'subtotal' => '$45.800', 'total' => '$61.700']);

        $this->post(route('orders.store'), ['customer_name' => 'Laura', 'customer_phone' => '3001234567']);

        $item = Order::sole()->items()->whereNotNull('combo_id')->sole();
        $this->assertSame($this->combo->id, $item->combo_id);
        $this->assertNull($item->plan_id);
        $this->assertSame('Combo Diamante – Incluye Netflix y Spotify (1 mes) x2', $item->summary());
        $this->assertSame('22900.00', $item->unit_price);
    }

    public function test_admin_creates_a_combo_with_at_least_two_plans(): void
    {
        $this->actingAs(User::factory()->create());
        $netflix = $this->monthlyPlan('netflix');

        $this->post(route('admin.combos.store'), ['name' => 'Solo uno', 'price_usd' => 3, 'duration_months' => 1, 'plans' => [$netflix->id]])
            ->assertSessionHasErrors(['plans' => 'Elige al menos dos planes para el combo.']);

        $this->post(route('admin.combos.store'), [
            'name' => 'Combo Música', 'price_usd' => 4.5, 'duration_months' => 1,
            'plans' => [$this->monthlyPlan('spotify')->id, $this->monthlyPlan('youtube-premium')->id],
            'is_active' => 1, 'is_available' => 1, 'is_featured' => 1,
        ])->assertRedirect(route('admin.combos.index'));

        $combo = Combo::where('name', 'Combo Música')->sole();
        $this->assertTrue($combo->is_featured);
        $this->assertSame(['spotify', 'youtube-premium'], $combo->plans->map->service->pluck('slug')->sort()->values()->all());

        $this->get(route('admin.combos.index'))->assertOk()->assertSee('Combo Música');
        $this->get(route('admin.combos.edit', $combo))->assertOk();
    }

    public function test_admin_marks_a_combo_as_sold_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('admin.combos.toggle', $this->combo))->assertRedirect();

        $this->assertFalse($this->combo->fresh()->is_available);
    }
}
