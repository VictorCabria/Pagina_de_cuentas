<?php

namespace App\Support;

use App\Enums\Currency;

/**
 * Resultado del cálculo de un precio en una moneda.
 */
final readonly class Price
{
    public function __construct(
        public Currency $currency,
        public float $original,
        public float $final,
        public int $discount,          // porcentaje aplicado (0 = sin promoción)
        public ?string $promotion,     // nombre de la promoción aplicada
        public int $months,
    ) {}

    public function hasDiscount(): bool
    {
        return $this->discount > 0;
    }

    public function isFree(): bool
    {
        return $this->final <= 0;
    }

    public function savings(): float
    {
        return $this->original - $this->final;
    }

    public function perMonth(): float
    {
        return $this->currency->round($this->final / max(1, $this->months));
    }

    public function formatted(): string
    {
        return $this->isFree() ? 'Gratis' : $this->currency->format($this->final);
    }

    public function formattedOriginal(): string
    {
        return $this->currency->format($this->original);
    }

    public function formattedPerMonth(): string
    {
        return $this->currency->format($this->perMonth());
    }
}
