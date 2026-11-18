<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpiryAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Service $netflix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-17 09:00');
        $this->seed(CatalogSeeder::class);
        Setting::put(['rate_mode' => 'manual', 'whatsapp' => '573506241755', 'alert_days' => '3']);
        $this->admin = User::factory()->create();
        $this->netflix = Service::where('slug', 'netflix')->firstOrFail();
    }

    private function subscription(string $expiresOn, string $name): Subscription
    {
        return Subscription::factory()->create([
            'service_id' => $this->netflix->id, 'customer_name' => $name, 'profile_name' => 'PIXEL', 'expires_on' => $expiresOn,
        ]);
    }

    public function test_accounts_expiring_within_the_window_are_alerted_once(): void
    {
        $this->subscription('2026-11-18', 'Mañana');
        $this->subscription('2026-11-20', 'En Tres');
        $this->subscription('2026-11-21', 'En Cuatro');   // fuera de la ventana
        $this->subscription('2026-11-10', 'Ya Vencida');  // ya pasó

        $this->artisan('cuentas:avisar')->expectsOutput('Avisadas 2 cuentas por vencer.')->assertSuccessful();

        $messages = $this->admin->fresh()->unreadNotifications->pluck('data.message')->sort()->values()->all();
        $this->assertSame([
            'Netflix de En Tres (perfil PIXEL) vence en 3 días, 20 nov.',
            'Netflix de Mañana (perfil PIXEL) vence mañana, 18 nov.',
        ], $messages);

        // La segunda corrida no repite
        $this->artisan('cuentas:avisar')->expectsOutput('No hay cuentas nuevas por vencer.');
        $this->assertCount(2, $this->admin->fresh()->notifications);
    }

    public function test_renewed_account_is_alerted_again_for_its_new_expiry(): void
    {
        $subscription = $this->subscription('2026-11-18', 'Laura');
        $this->artisan('cuentas:avisar');

        $subscription->update(['expires_on' => '2026-12-18']);
        $this->travelTo('2026-12-16 09:00');
        $this->artisan('cuentas:avisar')->expectsOutput('Avisada 1 cuenta por vencer.');

        $this->assertCount(2, $this->admin->fresh()->notifications);
    }

    public function test_alerts_never_call_external_services(): void
    {
        Http::fake();
        $this->subscription('2026-11-18', 'Laura');

        $this->artisan('cuentas:avisar');

        Http::assertNothingSent();
        $this->assertCount(1, $this->admin->fresh()->notifications);
    }

    public function test_reminder_message_uses_the_template_with_the_customer_data(): void
    {
        $this->travelTo('2026-11-13 09:00');
        $subscription = Subscription::factory()->create([
            'service_id' => Service::where('slug', 'crunchyroll')->value('id'),
            'customer_name' => 'Jhamal', 'customer_phone' => '573001234567', 'profile_name' => 'Jhamal',
            'expires_on' => '2026-11-13',
        ]);

        $this->assertSame(
            "Hola Jhamal. Le recordamos que su perfil CRUNCHYROLL Jhamal vence en 0 días, el viernes, 13 de noviembre a las 6.00pm.\n\n"
            ."✅ Si continúa, favor realizar el pago ANTES de la FECHA/HORA de corte.\n✅ Si NO continúa, agradecemos informarlo.\n\n"
            .'Por favor confirmar. Gracias.',
            $subscription->reminderMessage(),
        );
        $this->assertSame('https://wa.me/573001234567?text='.rawurlencode($subscription->reminderMessage()), $subscription->reminderUrl());

        // Plantilla y hora editables desde Ajustes
        Setting::put(['reminder_template' => 'Hola {nombre}, tu {plataforma} vence el {fecha} a las {hora} ({dias} días).', 'reminder_time' => '20:30']);
        $this->assertSame('Hola Jhamal, tu CRUNCHYROLL vence el viernes, 13 de noviembre a las 8.30pm (0 días).', $subscription->reminderMessage());
    }

    public function test_whatsapp_reminder_button_shows_the_customer_number_when_expiring(): void
    {
        $this->subscription('2026-11-19', 'Laura')->update(['customer_phone' => '573001112233']);
        $this->subscription('2027-01-30', 'Lejos')->update(['customer_phone' => '573004445566']);

        $response = $this->actingAs($this->admin)->get(route('admin.subscriptions.index'));

        // La que está por vencer trae el botón verde con su número y el recordatorio; la lejana no.
        $response->assertSee('Enviarle a Laura el recordatorio de vencimiento', false)
            ->assertSee('https://wa.me/573001112233?text='.rawurlencode('Hola Laura.'), false)
            ->assertDontSee('Enviarle a Lejos el recordatorio de vencimiento', false);

        $this->getJson(route('admin.notifications.status'))
            ->assertJsonPath('latest.phone', '+57 300 111 2233');
    }

    public function test_opening_the_panel_checks_automatically_and_shows_the_bell(): void
    {
        $this->subscription('2026-11-19', 'Laura');

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Notificaciones (1 sin leer)')
            ->assertSee('Netflix de Laura (perfil PIXEL) vence en 2 días, 19 nov.')
            ->assertSee('data-expiring="1"', false)
            ->assertSee('Cuenta por vencer')
            ->assertSee('en los próximos 3 días');
    }

    public function test_bell_status_updates_without_reloading(): void
    {
        $this->actingAs($this->admin)->getJson(route('admin.notifications.status'))
            ->assertExactJson(['count' => 0, 'expiring' => 0, 'latest' => null]);

        $this->subscription('2026-11-19', 'Laura');
        $this->artisan('cuentas:avisar');

        $this->getJson(route('admin.notifications.status'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('expiring', 1)
            ->assertJsonPath('latest.message', 'Netflix de Laura (perfil PIXEL) vence en 2 días, 19 nov.');
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $this->subscription('2026-11-19', 'Laura');
        $this->artisan('cuentas:avisar');
        $notification = $this->admin->fresh()->notifications->sole();

        $this->actingAs($this->admin)->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.subscriptions.index', ['servicio' => $this->netflix->id, 'estado' => 'vence-pronto']));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('admin.notifications.index'))->assertOk()->assertSee('Netflix de Laura');
    }

    public function test_cron_url_requires_the_secret(): void
    {
        config(['services.cron.secret' => 'super-secreto']);
        $this->subscription('2026-11-18', 'Laura');

        $this->get(route('cron.alerts'))->assertNotFound();
        $this->get(route('cron.alerts', ['token' => 'otro']))->assertNotFound();

        $this->getJson(route('cron.alerts'), ['Authorization' => 'Bearer super-secreto'])
            ->assertOk()
            ->assertExactJson(['alerted' => 1]);
    }
}
