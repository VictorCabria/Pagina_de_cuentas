<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Service $service;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(['rate_mode' => 'manual', 'usd_to_cop' => '4000']);
        $this->category = Category::create(['name' => 'Streaming']);
        $this->service = Service::create(['name' => 'Netflix', 'category_id' => $this->category->id]);
        $this->plan = $this->service->plans()->create(['name' => 'Perfil', 'duration_months' => 3, 'price_usd' => 10.99]);
    }

    private function price(Currency $currency = Currency::USD, ?Plan $plan = null)
    {
        app(Pricing::class)->refresh();

        return ($plan ?? $this->plan)->fresh()->price($currency);
    }

    public function test_without_promotion_returns_list_price(): void
    {
        $price = $this->price();

        $this->assertSame(10.99, $price->final);
        $this->assertFalse($price->hasDiscount());
        $this->assertSame('US$10.99', $price->formatted());
    }

    public function test_cop_is_converted_with_rate_and_rounded_to_hundreds(): void
    {
        // 10.99 × 4000 = 43.960 → 44.000
        $this->assertSame(44000.0, $this->price(Currency::COP)->final);
        $this->assertSame('$44.000', $this->price(Currency::COP)->formatted());
    }

    public function test_fixed_cop_price_overrides_rate(): void
    {
        $this->plan->update(['price_cop' => 43900]);

        $this->assertSame(43900.0, $this->price(Currency::COP)->final);
    }

    public function test_percentage_discount_in_both_currencies(): void
    {
        $this->plan->update(['price_cop' => 43900]);
        Promotion::create(['name' => 'Promo', 'percent' => 15]);

        $usd = $this->price();
        $cop = $this->price(Currency::COP);

        $this->assertSame(15, $usd->discount);
        $this->assertSame(9.34, $usd->final);           // 10.99 × 0.85 = 9.3415
        $this->assertSame(37300.0, $cop->final);        // 43.900 × 0.85 = 37.315 → 37.300
        $this->assertSame(43900.0, $cop->original);
    }

    public function test_hundred_percent_is_free(): void
    {
        Promotion::create(['name' => 'Gratis', 'percent' => 100]);

        $price = $this->price();
        $this->assertTrue($price->isFree());
        $this->assertSame('Gratis', $price->formatted());
    }

    public function test_best_promotion_wins_and_they_do_not_stack(): void
    {
        Promotion::create(['name' => 'Tienda', 'percent' => 10]);
        Promotion::create(['name' => 'Categoría', 'percent' => 30, 'category_id' => $this->category->id]);
        Promotion::create(['name' => 'Servicio', 'percent' => 20, 'service_id' => $this->service->id]);

        $price = $this->price();
        $this->assertSame(30, $price->discount);
        $this->assertSame('Categoría', $price->promotion);
    }

    public function test_promotion_scope_only_affects_its_target(): void
    {
        $other = Service::create(['name' => 'Spotify']);
        $otherPlan = $other->plans()->create(['name' => 'Cuenta', 'duration_months' => 1, 'price_usd' => 2.99]);
        Promotion::create(['name' => 'Solo Netflix', 'percent' => 50, 'service_id' => $this->service->id]);
        Promotion::create(['name' => 'Solo un plan', 'percent' => 40, 'plan_id' => $otherPlan->id]);

        $this->assertSame(50, $this->price()->discount);
        $this->assertSame(40, $this->price(Currency::USD, $otherPlan)->discount);
    }

    public function test_paused_expired_and_future_promotions_are_ignored(): void
    {
        Promotion::create(['name' => 'Pausada', 'percent' => 50, 'is_active' => false]);
        Promotion::create(['name' => 'Vencida', 'percent' => 50, 'ends_at' => now()->subDay()]);
        Promotion::create(['name' => 'Futura', 'percent' => 50, 'starts_at' => now()->addDay()]);

        $this->assertFalse($this->price()->hasDiscount());
    }

    public function test_per_month_price(): void
    {
        $this->assertSame(3.66, $this->price()->perMonth()); // 10.99 / 3
    }
}
