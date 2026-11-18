<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndOrderTest extends TestCase
{
    use RefreshDatabase;

    private Plan $netflixMonth;

    private Plan $netflixQuarter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4000', 'default_currency' => 'COP', 'whatsapp' => '573506241755']);

        $netflix = Service::where('slug', 'netflix')->firstOrFail();
        $this->netflixMonth = $netflix->plans()->where('duration_months', 1)->firstOrFail();     // $15.900
        $this->netflixQuarter = $netflix->plans()->where('duration_months', 3)->firstOrFail();   // $43.900
    }

    private function checkout(array $customer = ['customer_name' => 'Laura Gómez', 'customer_phone' => '300 123 4567'])
    {
        return $this->post(route('orders.store'), $customer);
    }

    public function test_adding_a_plan_shows_it_in_the_cart_with_its_total(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id])
            ->assertRedirect()
            ->assertSessionHas('cart_added', 'Netflix (1 mes) se agregó al carrito.');

        $this->post(route('cart.store'), ['plan_id' => $this->netflixQuarter->id, 'checkout' => 1])
            ->assertRedirect(route('cart.show'));

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSeeInOrder(['Netflix', '1 mes', '$15.900', 'Netflix', '3 meses', '$43.900', 'Total', '$59.800']);
    }

    public function test_sold_out_plan_cannot_be_added(): void
    {
        $max = Service::where('slug', 'max')->firstOrFail()->plans()->firstOrFail();

        $this->post(route('cart.store'), ['plan_id' => $max->id])
            ->assertSessionHas('cart_error', 'Ese plan está agotado en este momento.');

        $this->get(route('cart.show'))->assertSee('Tu carrito está vacío');
    }

    public function test_quantity_is_capped_and_zero_removes_the_line(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id, 'quantity' => 5]);
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id]);

        $this->get(route('cart.show'))->assertSee('$79.500'); // 5 × 15.900, no 6

        $this->patch(route('cart.update', $this->netflixMonth->cartKey()), ['quantity' => 0])->assertRedirect(route('cart.show'));
        $this->get(route('cart.show'))->assertSee('Tu carrito está vacío');
    }

    public function test_quantity_changes_from_the_cart_page_answer_without_reloading(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id]);
        $this->post(route('cart.store'), ['plan_id' => $this->netflixQuarter->id]);

        $this->patchJson(route('cart.update', $this->netflixMonth->cartKey()), ['quantity' => 2])
            ->assertOk()
            ->assertExactJson([
                'quantity' => 2, 'max' => 5, 'subtotal' => '$31.800', 'original' => null,
                'total' => '$75.700', 'count' => 3, 'canOrder' => true,
            ]);

        $this->deleteJson(route('cart.destroy', $this->netflixQuarter->cartKey()))
            ->assertOk()
            ->assertJson(['quantity' => 0, 'subtotal' => null, 'total' => '$31.800', 'count' => 2]);
    }

    public function test_checkout_creates_the_order_and_opens_whatsapp_with_it(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['plan_id' => $this->netflixQuarter->id]);

        $order = Order::first();
        $this->assertNull($order);

        $response = $this->checkout();

        $order = Order::with('items')->sole();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame('Laura Gómez', $order->customer_name);
        $this->assertSame('573001234567', $order->customer_phone);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('75700.00', $order->total);
        $this->assertSame([2, 1], $order->items->pluck('quantity')->all());
        $this->assertSame('15900.00', $order->items[0]->unit_price);

        $message = $order->whatsappMessage();
        $this->assertStringContainsString("Nuevo pedido {$order->number()}", $message);
        $this->assertStringContainsString('Netflix – Perfil individual (1 mes) x2: *$31.800*', $message);
        $this->assertStringContainsString('Total: $75.700 COP', $message);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('https://wa.me/573506241755?text='.rawurlencode($message), false);

        $this->get(route('cart.show'))->assertSee('Tu carrito está vacío');
    }

    public function test_order_uses_current_promotion_price(): void
    {
        $spotify = Service::where('slug', 'spotify')->firstOrFail()->plans()->where('duration_months', 1)->firstOrFail();

        $this->post(route('cart.store'), ['plan_id' => $spotify->id]);
        $this->checkout();

        $item = Order::sole()->items()->sole();
        $this->assertSame(20, $item->discount);
        $this->assertSame('9500.00', $item->unit_price);   // 11.900 − 20 %
        $this->assertSame('11900.00', $item->original_price);
    }

    public function test_checkout_requires_name_and_valid_phone(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id]);

        $this->checkout([])->assertSessionHasErrors([
            'customer_name' => 'Escribe tu nombre.',
            'customer_phone' => 'Escribe tu número de WhatsApp.',
        ]);

        $this->checkout(['customer_name' => 'Laura', 'customer_phone' => 'abc'])
            ->assertSessionHasErrors(['customer_phone' => 'Escribe un número válido, p. ej. 300 123 4567.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_items_sold_out_after_adding_are_removed_before_ordering(): void
    {
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id]);
        $this->netflixMonth->update(['is_available' => false]);

        $this->checkout()
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('cart_error', 'Quitamos del carrito lo que se agotó. Revisa tu pedido y vuelve a enviarlo.');

        $this->assertDatabaseCount('orders', 0);
        $this->get(route('cart.show'))->assertSee('Tu carrito está vacío');
    }

    public function test_empty_cart_cannot_be_ordered(): void
    {
        $this->checkout()->assertRedirect(route('cart.show'))->assertSessionHas('cart_error', 'Tu carrito está vacío.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_ordering_is_disabled_without_store_whatsapp(): void
    {
        Setting::put(['whatsapp' => '']);
        $this->post(route('cart.store'), ['plan_id' => $this->netflixMonth->id]);

        $this->checkout()->assertSessionHas('cart_error', 'Los pedidos por WhatsApp aún no están habilitados.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_page_is_only_reachable_by_its_token(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->number());
        $this->get('/pedido/'.$order->id)->assertNotFound();
    }
}
