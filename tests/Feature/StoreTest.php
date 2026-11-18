<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Service;
use App\Models\Setting;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4000', 'default_currency' => 'COP', 'whatsapp' => '573001234567']);
    }

    public function test_home_lists_active_services_with_their_starting_price(): void
    {
        Service::where('name', 'Max')->update(['is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Netflix')
            ->assertSee('$13.300') // perfil de 12 meses: 159.900 / 12
            ->assertSee('1 USD = $4.000 COP')
            ->assertDontSee('HBO, Warner y DC');
    }

    public function test_home_shows_the_discount_badge_of_a_running_promotion(): void
    {
        Promotion::create(['name' => 'Black Friday', 'percent' => 30, 'service_id' => Service::where('name', 'Spotify')->value('id'), 'is_active' => true]);

        $this->get(route('home'))->assertSee('-30%');
    }

    public function test_home_shows_the_licenses_section_only_when_there_are_active_licenses(): void
    {
        $this->get(route('home'))
            ->assertSee('id="licencias"', false)
            ->assertSeeInOrder(['Software profesional original', 'Adobe Creative Cloud', 'Elige tu plataforma']);

        Service::whereHas('category', fn ($query) => $query->where('slug', 'licencias'))->update(['is_active' => false]);

        $this->get(route('home'))->assertDontSee('id="licencias"', false);
    }

    public function test_service_page_shows_plans_and_add_to_cart_form(): void
    {
        $this->get(route('store.service', 'netflix'))
            ->assertOk()
            ->assertSee('$43.900')
            ->assertSee('action="'.route('cart.store').'"', false)
            ->assertSee('Agregar al carrito');
    }

    public function test_sold_out_service_is_clearly_marked(): void
    {
        Service::where('slug', 'netflix')->update(['is_available' => false]);

        $this->get(route('home'))->assertSee('Sin cupos');
        $this->get(route('store.service', 'netflix'))
            ->assertSee('Este servicio no tiene cupos en este momento')
            ->assertDontSee('Agregar al carrito');
    }

    public function test_floating_whatsapp_support_button_shows_the_number(): void
    {
        $this->get(route('home'))
            ->assertSee('¡Hola! 👋 ¿Tienes dudas?')
            ->assertSee('+57 300 123 4567')
            ->assertSee('https://wa.me/573001234567?text=', false);
    }

    public function test_no_support_button_without_whatsapp(): void
    {
        Setting::put(['whatsapp' => '']);

        $this->get(route('home'))->assertDontSee('data-wa-toggle', false);
    }

    public function test_inactive_service_page_is_not_found(): void
    {
        Service::where('slug', 'netflix')->update(['is_active' => false]);

        $this->get(route('store.service', 'netflix'))->assertNotFound();
    }

    public function test_visitor_can_switch_currency(): void
    {
        $this->from(route('store.service', 'netflix'))
            ->get(route('store.currency', 'USD'))
            ->assertRedirect(route('store.service', 'netflix'))
            ->assertSessionHas('currency', 'USD');

        $this->get(route('store.service', 'netflix'))->assertSee('US$10.99');
    }

    public function test_unknown_currency_is_not_found(): void
    {
        $this->get('/moneda/EUR')->assertNotFound();
    }
}
