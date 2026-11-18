<?php

namespace App\Models;

use App\Enums\Currency;
use App\Models\Concerns\HasSlug;
use App\Support\Price;
use App\Support\Pricing;
use App\Support\Sellable;
use Database\Factories\ComboFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Varias plataformas juntas a un precio especial ("Combo Diamante": Disney+ + Crunchyroll).
 * Tiene precio propio: las promociones de los planes no se le aplican.
 */
#[Fillable([
    'name', 'slug', 'description', 'image', 'duration_months', 'price_usd', 'price_cop',
    'is_active', 'is_available', 'is_featured', 'sort_order',
])]
class Combo extends Model implements Sellable
{
    /** @use HasFactory<ComboFactory> */
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'price_usd' => 'decimal:2',
            'price_cop' => 'integer',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class)->orderBy('plans.service_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function price(Currency $currency): Price
    {
        $amount = app(Pricing::class)->convert((float) $this->price_usd, $this->price_cop, $currency);

        return new Price($currency, $amount, $amount, 0, null, $this->duration_months);
    }

    /** Lo que costarían sus planes comprados por separado (precio de lista). */
    public function regularPrice(Currency $currency): float
    {
        $pricing = app(Pricing::class);

        return $this->plans->sum(fn (Plan $plan) => $pricing->base($plan, $currency));
    }

    public function savings(Currency $currency): float
    {
        return max(0.0, $this->regularPrice($currency) - $this->price($currency)->final);
    }

    /** Se puede vender si está activo, con cupos y todos sus planes disponibles. */
    public function purchasable(): bool
    {
        return $this->is_active
            && $this->is_available
            && $this->plans->isNotEmpty()
            && $this->plans->every(fn (Plan $plan) => $plan->purchasable());
    }

    public function imageUrl(): ?string
    {
        return blank($this->image) ? null : Storage::disk('public')->url($this->image);
    }

    /** "Disney+ y Crunchyroll" */
    public function includedLabel(): string
    {
        return $this->plans->map(fn (Plan $plan) => $plan->service->name)->unique()->join(', ', ' y ');
    }

    public function durationLabel(): string
    {
        return (new Plan(['duration_months' => $this->duration_months]))->durationLabel();
    }

    public function cartKey(): string
    {
        return 'combo-'.$this->id;
    }

    public function cartTitle(): string
    {
        return $this->name;
    }

    public function cartSubtitle(): string
    {
        return "Incluye {$this->includedLabel()} · {$this->durationLabel()}";
    }

    public function cartLabel(): string
    {
        return $this->name;
    }

    public function storeUrl(): string
    {
        return route('home').'#combos';
    }
}
