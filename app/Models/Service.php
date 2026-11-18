<?php

namespace App\Models;

use App\Enums\Currency;
use App\Models\Concerns\HasSlug;
use App\Support\Price;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Plataforma que se vende (Netflix, Spotify, ChatGPT...).
 */
#[Fillable([
    'category_id', 'name', 'slug', 'tagline', 'description', 'logo', 'color', 'features',
    'is_available', 'is_active', 'is_featured', 'sort_order',
])]
class Service extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class)->chaperone()->orderBy('sort_order')->orderBy('duration_months');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** Se puede comprar: el servicio tiene cupos y al menos un plan disponible. */
    public function inStock(): bool
    {
        return $this->is_available && $this->plans->contains('is_available', true);
    }

    /** Plan disponible más barato por mes, para mostrar "desde ... /mes". */
    public function cheapestPrice(Currency $currency): ?Price
    {
        return $this->plans
            ->where('is_available', true)
            ->map(fn (Plan $plan) => $plan->price($currency))
            ->sortBy(fn (Price $price) => $price->perMonth())
            ->first();
    }

    /** Mayor descuento vigente entre sus planes (0 si no hay promoción). */
    public function bestDiscount(Currency $currency): int
    {
        return (int) $this->plans->map(fn (Plan $plan) => $plan->price($currency)->discount)->max();
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        return str_starts_with($this->logo, 'images/') ? asset($this->logo) : Storage::disk('public')->url($this->logo);
    }

    /** Inicial para mostrar cuando no hay logo. */
    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
