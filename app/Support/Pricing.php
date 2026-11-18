<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Plan;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Calcula precios en la moneda elegida aplicando la mejor promoción vigente.
 *
 *   precio final = precio de lista × (100 − %) / 100, redondeado según la moneda.
 *
 * Es un singleton (scoped): las promociones se consultan una sola vez por petición.
 */
class Pricing
{
    /** @var Collection<int, Promotion>|null */
    private ?Collection $promotions = null;

    public function rate(): float
    {
        return app(ExchangeRate::class)->current();
    }

    /** Precio de lista (sin descuento) en la moneda pedida. */
    public function base(Plan $plan, Currency $currency): float
    {
        return $this->convert((float) $plan->price_usd, $plan->price_cop, $currency);
    }

    /** Precio en dólares → moneda pedida, salvo que haya un precio fijo en pesos. */
    public function convert(float $priceUsd, ?int $fixedPriceCop, Currency $currency): float
    {
        return match ($currency) {
            Currency::USD => $priceUsd,
            Currency::COP => $fixedPriceCop !== null
                ? (float) $fixedPriceCop
                : Currency::COP->round($priceUsd * $this->rate()),
        };
    }

    /** Si varias promociones aplican a un plan, gana la de mayor porcentaje. */
    public function bestPromotion(Plan $plan): ?Promotion
    {
        return $this->promotions()
            ->filter(fn (Promotion $promo) => $promo->appliesTo($plan))
            ->sortByDesc('percent')
            ->first();
    }

    public function for(Plan $plan, Currency $currency): Price
    {
        $original = $this->base($plan, $currency);
        $promotion = $this->bestPromotion($plan);
        $percent = min(100, max(0, $promotion?->percent ?? 0));

        $final = $currency->round($original * (100 - $percent) / 100);

        return new Price($currency, $original, max(0.0, $final), $percent, $promotion?->name, $plan->duration_months);
    }

    /** Olvida las promociones cargadas (tras cambiarlas en el panel o en pruebas). */
    public function refresh(): void
    {
        $this->promotions = null;
    }

    /** @return Collection<int, Promotion> */
    private function promotions(): Collection
    {
        return $this->promotions ??= Promotion::running()->get();
    }
}
