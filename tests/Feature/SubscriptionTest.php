<?php

namespace Tests\Feature;

use App\Models\Combo;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Support\SubscriptionsSpreadsheet;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    private function service(string $slug): Service
    {
        return Service::where('slug', $slug)->firstOrFail();
    }

    /** Orden con 2 × Netflix 3 meses y un combo Disney+ + Spotify de 1 mes. */
    private function paidOrderCandidate(): Order
    {
        $order = Order::factory()->create(['customer_name' => 'Laura Gómez', 'customer_phone' => '573001234567']);
        $netflixQuarter = $this->service('netflix')->plans()->where('duration_months', 3)->firstOrFail();
        $combo = Combo::factory()->create(['name' => 'Combo Prueba']);
        $combo->plans()->attach([
            $this->service('disney')->plans()->where('duration_months', 1)->value('id'),
            $this->service('spotify')->plans()->where('duration_months', 1)->value('id'),
        ]);

        $order->items()->createMany([
            ['plan_id' => $netflixQuarter->id, 'service_name' => 'Netflix', 'plan_name' => 'Perfil', 'duration_months' => 3, 'quantity' => 2, 'unit_price' => 43900, 'original_price' => 43900],
            ['combo_id' => $combo->id, 'service_name' => 'Combo Prueba', 'plan_name' => 'Incluye', 'duration_months' => 1, 'quantity' => 1, 'unit_price' => 20000, 'original_price' => 20000],
        ]);

        return $order;
    }

    public function test_marking_an_order_paid_adds_its_accounts_with_customer_and_dates(): void
    {
        $this->travelTo('2026-11-17');
        $order = $this->paidOrderCandidate();

        $this->patch(route('admin.orders.update', $order), ['status' => 'paid'])
            ->assertSessionHas('status', "Orden {$order->number()}: Pagada. Se agregaron 4 cuentas en «Cuentas»: falta ponerles el correo.");

        $subscriptions = Subscription::with('service')->where('order_id', $order->id)->get();
        $this->assertSame(['disney', 'netflix', 'netflix', 'spotify'], $subscriptions->pluck('service.slug')->sort()->values()->all());
        $this->assertTrue($subscriptions->every(fn ($row) => $row->customer_name === 'Laura Gómez' && $row->customer_phone === '573001234567' && $row->account_email === null));
        $this->assertSame('2027-02-17', $subscriptions->firstWhere('service.slug', 'netflix')->expires_on->toDateString());
        $this->assertSame('2026-12-17', $subscriptions->firstWhere('service.slug', 'spotify')->expires_on->toDateString());

        // Marcarla Entregada después no duplica las cuentas
        $this->patch(route('admin.orders.update', $order), ['status' => 'delivered']);
        $this->assertSame(4, Subscription::where('order_id', $order->id)->count());
    }

    public function test_pending_or_cancelled_orders_do_not_add_accounts(): void
    {
        $order = $this->paidOrderCandidate();

        $this->patch(route('admin.orders.update', $order), ['status' => 'cancelled']);

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_admin_completes_an_account_and_the_password_is_stored_encrypted(): void
    {
        $subscription = Subscription::factory()->withoutAccount()->create(['service_id' => $this->service('netflix')->id]);

        $this->get(route('admin.subscriptions.index'))->assertSee('+ Poner correo');

        $this->put(route('admin.subscriptions.update', $subscription), [
            'service_id' => $subscription->service_id,
            'customer_name' => 'Laura',
            'customer_phone' => '300 123 4567',
            'account_email' => 'cuenta@ejemplo.com',
            'account_password' => 'Secreta123',
            'profile_name' => 'PIXEL',
            'pin' => '1818',
            'starts_on' => '2026-11-17',
            'expires_on' => '2026-12-17',
        ])->assertRedirect(route('admin.subscriptions.index', ['servicio' => $subscription->service_id]));

        $subscription->refresh();
        $this->assertSame('cuenta@ejemplo.com', $subscription->account_email);
        $this->assertSame('Secreta123', $subscription->account_password);
        $this->assertSame('573001234567', $subscription->customer_phone);
        $this->assertNotSame('Secreta123', DB::table('subscriptions')->value('account_password'));

        $this->assertStringContainsString('Contraseña: Secreta123', $subscription->credentialsMessage());
        $this->assertStringContainsString('Tu perfil: PIXEL', $subscription->credentialsMessage());
        $this->assertStringContainsString('PIN de tu perfil: 1818', $subscription->credentialsMessage());

        $this->get(route('admin.subscriptions.index'))->assertSeeInOrder(['cuenta@ejemplo.com', 'PIXEL', 'PIN', '1818']);
    }

    public function test_admin_adds_an_account_manually(): void
    {
        $this->post(route('admin.subscriptions.store'), [
            'service_id' => $this->service('crunchyroll')->id,
            'customer_name' => 'Miguel',
            'account_email' => 'anime@ejemplo.com',
            'starts_on' => '2026-11-17',
            'expires_on' => '2026-11-01',
        ])->assertSessionHasErrors(['expires_on' => 'El vencimiento no puede ser antes del inicio.']);

        $this->post(route('admin.subscriptions.store'), [
            'service_id' => $this->service('crunchyroll')->id,
            'customer_name' => 'Miguel',
            'account_email' => 'anime@ejemplo.com',
            'starts_on' => '2026-11-17',
            'expires_on' => '2027-11-17',
        ])->assertRedirect(route('admin.subscriptions.index'));

        $this->assertDatabaseHas('subscriptions', ['customer_name' => 'Miguel', 'account_email' => 'anime@ejemplo.com', 'order_id' => null]);
    }

    public function test_status_filters_and_search(): void
    {
        $this->travelTo('2026-11-17');
        Setting::put(['alert_days' => '0']); // sin avisos en la campana: aquí solo se prueba la tabla
        $netflix = $this->service('netflix')->id;
        Subscription::factory()->withoutAccount()->create(['service_id' => $netflix, 'customer_name' => 'Sin Correo']);
        Subscription::factory()->create(['service_id' => $netflix, 'customer_name' => 'Por Vencer', 'expires_on' => '2026-11-20']);
        Subscription::factory()->create(['service_id' => $netflix, 'customer_name' => 'Ya Vencida', 'expires_on' => '2026-11-10']);
        Subscription::factory()->create(['service_id' => $netflix, 'customer_name' => 'Al Dia', 'expires_on' => '2027-01-01']);

        $this->get(route('admin.subscriptions.index', ['estado' => 'falta-correo']))
            ->assertSee('Sin Correo')->assertDontSee('Por Vencer')->assertDontSee('Ya Vencida');
        $this->get(route('admin.subscriptions.index', ['estado' => 'vence-pronto']))
            ->assertSee('Por Vencer')->assertSee('quedan 3 días')->assertDontSee('Al Dia');
        $this->get(route('admin.subscriptions.index', ['estado' => 'vencidas']))
            ->assertSee('Ya Vencida')->assertSee('venció hace 7 días')->assertDontSee('Al Dia');
        $this->get(route('admin.subscriptions.index', ['q' => 'al dia']))
            ->assertSee('Al Dia')->assertDontSee('Sin Correo');
    }

    public function test_renew_adds_a_month_from_the_expiry_or_from_today_if_expired(): void
    {
        $this->travelTo('2026-11-17');
        $active = Subscription::factory()->create(['expires_on' => '2026-11-20']);
        $expired = Subscription::factory()->create(['expires_on' => '2026-11-01']);

        $this->patch(route('admin.subscriptions.renew', $active));
        $this->patch(route('admin.subscriptions.renew', $expired));

        $this->assertSame('2026-12-20', $active->fresh()->expires_on->toDateString());
        $this->assertSame('2026-12-17', $expired->fresh()->expires_on->toDateString());
    }

    public function test_guests_cannot_see_accounts(): void
    {
        auth()->logout();

        $this->get(route('admin.subscriptions.index'))->assertRedirect(route('login'));
    }

    public function test_form_suggests_emails_but_never_sends_passwords(): void
    {
        $netflix = $this->service('netflix');
        Subscription::factory()->create([
            'service_id' => $netflix->id, 'account_email' => 'cuenta@ejemplo.com', 'account_password' => 'Secreta123',
        ]);

        $this->get(route('admin.subscriptions.create'))->assertOk()
            ->assertSee('cuenta@ejemplo.com')
            ->assertDontSee('Secreta123');

        $this->getJson(route('admin.subscriptions.known-password', ['service_id' => $netflix->id, 'email' => 'cuenta@ejemplo.com']))
            ->assertOk()
            ->assertExactJson(['password' => 'Secreta123']);

        $this->getJson(route('admin.subscriptions.known-password', ['service_id' => $netflix->id, 'email' => 'otra@ejemplo.com']))
            ->assertNotFound();
        $this->getJson(route('admin.subscriptions.known-password', ['service_id' => $this->service('spotify')->id, 'email' => 'cuenta@ejemplo.com']))
            ->assertNotFound();
    }

    public function test_guests_cannot_ask_for_a_saved_password(): void
    {
        $netflix = $this->service('netflix');
        Subscription::factory()->create([
            'service_id' => $netflix->id, 'account_email' => 'cuenta@ejemplo.com', 'account_password' => 'Secreta123',
        ]);
        auth()->logout();

        $this->getJson(route('admin.subscriptions.known-password', ['service_id' => $netflix->id, 'email' => 'cuenta@ejemplo.com']))
            ->assertUnauthorized();
    }

    public function test_export_downloads_an_excel_with_one_block_per_platform(): void
    {
        $this->travelTo('2026-11-18');
        Subscription::factory()->create([
            'service_id' => $this->service('netflix')->id,
            'customer_name' => 'Celianeth',
            'customer_phone' => '50764725095',
            'account_email' => 'cuenta@example.com',
            'account_password' => 'clave15%',
            'profile_name' => 'PIXEL',
            'pin' => '0505',
            'starts_on' => '2026-03-01',
            'expires_on' => '2027-03-01',
            'notes' => 'Pago 2$',
        ]);
        Subscription::factory()->create(['service_id' => $this->service('spotify')->id, 'customer_name' => 'Patri', 'profile_name' => null]);

        $response = $this->get(route('admin.subscriptions.export'))
            ->assertOk()
            ->assertDownload('cuentas-2026-11-18.xlsx');

        $rows = $this->readSpreadsheet($response->streamedContent());

        $this->assertSame('CUENTA DE NETFLIX', $rows[3]['C']);
        $this->assertSame(SubscriptionsSpreadsheet::HEADINGS, array_values(array_slice($rows[4], 2)));
        $this->assertSame(
            ['+507 6472-5095', 'Celianeth (PIXEL)', 'cuenta@example.com', 'clave15%', '0505', '1 de marzo de 2026', '1 de marzo de 2027', 'Pago 2$'],
            array_values(array_slice($rows[5], 2)),
        );
        $this->assertNull($rows[6]['C']);
        $this->assertSame('CUENTA DE SPOTIFY', $rows[7]['C']);
        $this->assertSame('Patri', $rows[9]['D']);
    }

    public function test_guests_cannot_export_accounts(): void
    {
        auth()->logout();

        $this->get(route('admin.subscriptions.export'))->assertRedirect(route('login'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSpreadsheet(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'cuentas');
        file_put_contents($path, $contents);

        try {
            return IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, true);
        } finally {
            unlink($path);
        }
    }
}
