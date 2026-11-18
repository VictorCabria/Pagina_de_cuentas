<?php

namespace App\Models;

use App\Enums\Currency;
use App\Support\Price;
use App\Support\Pricing;
use App\Support\Sellable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Opción de compra de un servicio (perfil 1 mes, cuenta completa 12 meses...).
 * price_cop vacío = se calcula con la tasa USD→COP de los ajustes.
 */
#[Fillable(['service_id', 'name', 'duration_months', 'price_usd', 'price_cop', 'is_available', 'sort_order'])]
class Plan extends Model implements Sellable
{
    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'price_cop' => 'integer',
            'duration_months' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class);
    }

    public function price(Currency $currency): Price
    {
        return app(Pricing::class)->for($this, $currency);
    }

    /** Disponible para la venta: el plan y su servicio tienen cupos. */
    public function purchasable(): bool
    {
        return $this->is_available && $this->service->is_available && $this->service->is_active;
    }

    public function durationLabel(): string
    {
        return match (true) {
            $this->duration_months === 1 => '1 mes',
            $this->duration_months === 12 => '1 año',
            $this->duration_months % 12 === 0 => ($this->duration_months / 12).' años',
            default => $this->duration_months.' meses',
        };
    }

    public function cartKey(): string
    {
        return 'plan-'.$this->id;
    }

    public function cartTitle(): string
    {
        return $this->service->name;
    }

    public function cartSubtitle(): string
    {
        return "{$this->name} · {$this->durationLabel()}";
    }

    public function cartLabel(): string
    {
        return "{$this->service->name} ({$this->durationLabel()})";
    }

    public function storeUrl(): string
    {
        return route('store.service', $this->service);
    }
}
