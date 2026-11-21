<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4000', 'default_currency' => 'COP']);
    }

    public function test_guests_cannot_see_finances(): void
    {
        $this->get(route('admin.finances'))->assertRedirect(route('login'));
    }

    public function test_only_paid_orders_count_as_income_and_amounts_are_converted(): void
    {
        $this->travelTo('2026-11-15 10:00');
        Order::factory()->status(OrderStatus::Paid)->create(['total' => 40000]);
        Order::factory()->status(OrderStatus::Delivered)->create(['currency' => Currency::USD, 'total' => 5]);
        Order::factory()->status(OrderStatus::Pending)->create(['total' => 900000]);
        Order::factory()->status(OrderStatus::Cancelled)->create(['total' => 800000]);
        Purchase::factory()->create(['amount' => 2, 'currency' => Currency::USD]);

        $this->actingAs(User::factory()->create());

        // Ganado 40.000 + 5 USD (20.000) = 60.000; invertido 2 USD = 8.000.
        $this->get(route('admin.finances'))->assertOk()
            ->assertViewHas('totals', ['income' => 60000.0, 'investment' => 8000.0, 'profit' => 52000.0, 'orders' => 2, 'purchases' => 1])
            ->assertSee('$52.000')
            ->assertSee('Noviembre 2026');

        $this->get(route('admin.finances', ['moneda' => 'USD']))
            ->assertViewHas('totals', fn (array $totals) => $totals['income'] === 15.0 && $totals['investment'] === 2.0)
            ->assertSee('US$13.00');
    }

    public function test_months_group_income_by_payment_date_and_investment_by_purchase_date(): void
    {
        $this->travelTo('2026-10-20');
        Order::factory()->status(OrderStatus::Paid)->create(['total' => 30000]);
        Purchase::factory()->create(['purchased_on' => '2026-11-02', 'amount' => 10000]);

        $this->actingAs(User::factory()->create());

        $monthly = $this->get(route('admin.finances'))->assertOk()->viewData('monthly');

        $this->assertSame(['2026-11', '2026-10'], $monthly->keys()->all());
        $this->assertSame(-10000.0, $monthly['2026-11']['profit']);
        $this->assertSame(30000.0, $monthly['2026-10']['profit']);
    }

    public function test_paid_date_is_kept_when_order_moves_from_paid_to_delivered(): void
    {
        $order = Order::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->travelTo('2026-11-01');
        $this->patch(route('admin.orders.update', $order), ['status' => 'paid']);
        $this->travelTo('2026-11-10');
        $this->patch(route('admin.orders.update', $order), ['status' => 'delivered']);

        $this->assertSame('2026-11-01', $order->fresh()->paid_at->toDateString());
    }

    public function test_dashboard_shows_totals_in_selected_currency(): void
    {
        Order::factory()->status(OrderStatus::Paid)->create(['total' => 40000]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard', ['moneda' => 'USD']))
            ->assertOk()
            ->assertSee('Ganado (ventas)')
            ->assertSee('US$10.00');
    }

    public function test_chart_fills_months_without_activity_with_zeros(): void
    {
        $this->travelTo('2026-11-15');
        Purchase::factory()->create(['purchased_on' => '2026-09-10', 'amount' => 50000]);
        Order::factory()->status(OrderStatus::Paid)->create(['total' => 70000]);

        $this->actingAs(User::factory()->create());

        $chart = $this->get(route('admin.finances'))->assertOk()->viewData('chart');

        $this->assertSame(['2026-06', '2026-07', '2026-08', '2026-09', '2026-10', '2026-11'], array_map(fn ($point) => $point['month']->format('Y-m'), $chart));
        $this->assertSame(50000.0, $chart[3]['investment']);
        $this->assertSame(0.0, $chart[4]['income']);
        $this->assertSame(70000.0, $chart[5]['income']);
    }

    public function test_guests_cannot_manage_purchases(): void
    {
        $purchase = Purchase::factory()->create();

        $this->get(route('admin.purchases.index'))->assertRedirect(route('login'));
        $this->post(route('admin.purchases.store'), ['amount' => 1000])->assertRedirect(route('login'));
        $this->delete(route('admin.purchases.destroy', $purchase))->assertRedirect(route('login'));

        $this->assertModelExists($purchase);
    }

    public function test_admin_records_a_purchase_and_it_counts_as_investment(): void
    {
        $service = Service::create(['name' => 'Netflix']);
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.purchases.store'), [
            'service_id' => $service->id, 'amount' => '12.5', 'currency' => 'USD', 'purchased_on' => today()->toDateString(),
        ])->assertRedirect(route('admin.purchases.index'))->assertSessionHasNoErrors();

        $purchase = Purchase::sole();
        $this->assertSame('12.50', $purchase->amount);
        $this->assertSame(Currency::USD, $purchase->currency);

        $this->get(route('admin.purchases.index'))->assertOk()->assertSee('Netflix')->assertSee('$50.000');

        $this->put(route('admin.purchases.update', $purchase), [
            'description' => 'Proveedor nuevo', 'amount' => '30000', 'currency' => 'COP', 'purchased_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Proveedor nuevo', $purchase->fresh()->label());

        $this->delete(route('admin.purchases.destroy', $purchase));
        $this->assertModelMissing($purchase);
    }

    public function test_purchase_needs_a_platform_or_detail_and_a_positive_amount(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.purchases.store'), ['amount' => '0', 'currency' => 'EUR', 'purchased_on' => today()->toDateString()])
            ->assertSessionHasErrors(['service_id', 'amount', 'currency']);

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_expires_the_given_months_after_the_purchase_date(): void
    {
        $this->travelTo('2026-11-20 10:00');
        $service = Service::create(['name' => 'Netflix']);
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.purchases.store'), [
            'service_id' => $service->id, 'amount' => '20000', 'currency' => 'COP',
            'purchased_on' => '2026-11-20', 'duration_months' => '6',
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::sole();
        $this->assertSame(6, $purchase->duration_months);
        $this->assertSame('2027-05-20', $purchase->expires_on->toDateString());
        $this->get(route('admin.purchases.index'))->assertSee('20 may. 2027')->assertSee('6 meses');

        // Al cambiar la fecha de compra se recalcula, sin pasarse de mes (31 ene + 1 = 28 feb).
        $this->put(route('admin.purchases.update', $purchase), [
            'service_id' => $service->id, 'amount' => '20000', 'currency' => 'COP',
            'purchased_on' => '2027-01-31', 'duration_months' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('2027-02-28', $purchase->fresh()->expires_on->toDateString());

        // Sin meses (otro gasto) no vence.
        $this->put(route('admin.purchases.update', $purchase), [
            'description' => 'Publicidad', 'amount' => '20000', 'currency' => 'COP', 'purchased_on' => '2026-11-20',
        ])->assertSessionHasNoErrors();
        $this->assertNull($purchase->fresh()->duration_months);
        $this->assertNull($purchase->fresh()->expires_on);
    }

    public function test_purchase_list_masks_emails_and_does_not_send_notes(): void
    {
        $service = Service::create(['name' => 'Crunchyroll']);
        $purchase = Purchase::factory()->create([
            'service_id' => $service->id,
            'description' => 'fiera152022@gmail.com 4 personas',
            'notes' => 'clave del proveedor: secreta123',
        ]);
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.purchases.index'))->assertOk()
            ->assertSee('fi*********@gmail.com 4 personas')
            ->assertDontSee('fiera152022')
            ->assertDontSee('secreta123');

        $this->get(route('admin.purchases.edit', $purchase))->assertOk()
            ->assertSee('fiera152022@gmail.com')
            ->assertSee('secreta123');
    }

    public function test_purchase_duration_must_be_between_one_and_thirty_six_months(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.purchases.store'), [
            'description' => 'Cuenta', 'amount' => '20000', 'currency' => 'COP',
            'purchased_on' => today()->toDateString(), 'duration_months' => '0',
        ])->assertSessionHasErrors('duration_months');

        $this->assertDatabaseCount('purchases', 0);
    }
}
