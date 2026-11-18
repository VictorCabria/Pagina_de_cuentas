<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_orders(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->patch(route('admin.orders.update', $order), ['status' => 'paid'])->assertRedirect(route('login'));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_orders_list_filters_by_status(): void
    {
        $pending = Order::factory()->create(['customer_name' => 'Cliente Pendiente']);
        $paid = Order::factory()->status(OrderStatus::Paid)->create(['customer_name' => 'Cliente Pagado']);

        $this->actingAs(User::factory()->create());

        $this->get(route('admin.orders.index'))->assertOk()->assertSee($pending->number())->assertSee($paid->number());
        $this->get(route('admin.orders.index', ['estado' => 'paid']))
            ->assertSee('Cliente Pagado')
            ->assertDontSee('Cliente Pendiente');
        $this->get(route('admin.orders.show', $pending))->assertOk()->assertSee('Cliente Pendiente');
    }

    public function test_admin_updates_status_and_notes(): void
    {
        $order = Order::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->patch(route('admin.orders.update', $order), ['status' => 'delivered', 'notes' => 'Pagó por Nequi'])
            ->assertRedirect()
            ->assertSessionHas('status', "Orden {$order->number()}: Entregada.");

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame('Pagó por Nequi', $order->notes);

        $this->patch(route('admin.orders.update', $order), ['status' => 'lost'])->assertSessionHasErrors('status');
    }

    public function test_admin_deletes_an_order(): void
    {
        $order = Order::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->delete(route('admin.orders.destroy', $order))->assertRedirect(route('admin.orders.index'));

        $this->assertModelMissing($order);
    }
}
