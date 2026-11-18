<?php

namespace App\Enums;

use App\Models\Setting;

enum Currency: string
{
    case USD = 'USD';
    case COP = 'COP';

    /** Moneda elegida por el visitante o, si no eligió, la de los ajustes. */
    public static function current(): self
    {
        return self::tryFrom((string) session('currency')) ?? self::from(Setting::get('default_currency'));
    }

    public function label(): string
    {
        return match ($this) {
            self::USD => 'Dólar (USD)',
            self::COP => 'Peso colombiano (COP)',
        };
    }

    /**
     * Redondeo de los precios finales: centavos en dólares,
     * centenas en pesos (13.515 → 13.500).
     */
    public function round(float $amount): float
    {
        return match ($this) {
            self::USD => round($amount, 2),
            self::COP => round($amount, -2),
        };
    }

    public function format(float $amount): string
    {
        return match ($this) {
            self::USD => 'US$'.number_format($amount, 2, '.', ','),
            self::COP => '$'.number_format($amount, 0, ',', '.'),
        };
    }
}
