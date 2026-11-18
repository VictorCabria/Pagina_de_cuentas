<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    private const YAHOO = 'query1.finance.yahoo.com/*';

    private const BACKUP = 'cdn.jsdelivr.net/*';

    private function exchangeRate(): ExchangeRate
    {
        return app(ExchangeRate::class);
    }

    public function test_automatic_mode_uses_the_live_market_rate(): void
    {
        Http::fake([self::YAHOO => Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 3316.641]]]]])]);

        $this->assertSame(3316.64, $this->exchangeRate()->current());
        $this->assertSame('Yahoo Finance', $this->exchangeRate()->source());
        $this->assertNotNull($this->exchangeRate()->updatedAt());
    }

    public function test_rate_is_fetched_at_most_once_per_hour(): void
    {
        Http::fake([self::YAHOO => Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 3300]]]]])]);

        $this->exchangeRate()->current();
        $this->exchangeRate()->current();
        Http::assertSentCount(1);

        $this->travel(61)->minutes();
        $this->exchangeRate()->current();
        Http::assertSentCount(2);
    }

    public function test_falls_back_to_the_backup_source(): void
    {
        Http::fake([
            self::YAHOO => Http::response('error', 500),
            self::BACKUP => Http::response(['usd' => ['cop' => 3320.5]]),
        ]);

        $this->assertSame(3320.5, $this->exchangeRate()->current());
        $this->assertSame('Currency API', $this->exchangeRate()->source());
    }

    public function test_keeps_last_known_rate_when_sources_fail_or_return_nonsense(): void
    {
        Setting::put(['usd_to_cop' => '3500']);
        Http::fake([
            self::YAHOO => Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 0]]]]]),
            self::BACKUP => Http::response('error', 500),
        ]);

        $this->assertFalse($this->exchangeRate()->refresh());
        $this->assertSame(3500.0, $this->exchangeRate()->current());
    }

    public function test_manual_mode_never_calls_the_sources(): void
    {
        Http::fake();
        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4100']);

        $this->assertSame(4100.0, $this->exchangeRate()->current());
        Http::assertNothingSent();
    }

    public function test_admin_can_switch_to_automatic_and_refresh_now(): void
    {
        Http::fake([self::YAHOO => Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 3316]]]]])]);
        $this->actingAs(User::factory()->create());

        $this->put(route('admin.settings.update'), [
            'store_name' => 'Tienda', 'rate_mode' => 'auto', 'usd_to_cop' => '9999', 'default_currency' => 'COP',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('auto', Setting::get('rate_mode'));
        $this->assertSame('3316', Setting::get('usd_to_cop'));

        $this->post(route('admin.settings.rate'))
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('status', 'Tasa actualizada: 1 USD = $3.316 COP.');
    }
}
