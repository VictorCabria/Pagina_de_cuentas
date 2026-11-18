<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Descuento de 1 a 100 %. Alcance según la columna llena:
 * ninguna = toda la tienda, category_id, service_id o plan_id.
 */
#[Fillable(['name', 'percent', 'category_id', 'service_id', 'plan_id', 'starts_at', 'ends_at', 'is_active'])]
class Promotion extends Model
{
    protected function casts(): array
    {
        return [
            'percent' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Activas y dentro de sus fechas en este momento. */
    public function scopeRunning(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /** 'Activa', 'Programada', 'Vencida' o 'Pausada'. */
    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'Pausada',
            $this->ends_at?->isPast() ?? false => 'Vencida',
            $this->starts_at?->isFuture() ?? false => 'Programada',
            default => 'Activa',
        };
    }

    public function appliesTo(Plan $plan): bool
    {
        return match (true) {
            $this->plan_id !== null => $this->plan_id === $plan->id,
            $this->service_id !== null => $this->service_id === $plan->service_id,
            $this->category_id !== null => $this->category_id === $plan->service->category_id,
            default => true,
        };
    }

    public function targetLabel(): string
    {
        return match (true) {
            $this->plan_id !== null => 'Plan: '.$this->plan?->service?->name.' · '.$this->plan?->name,
            $this->service_id !== null => 'Servicio: '.$this->service?->name,
            $this->category_id !== null => 'Categoría: '.$this->category?->name,
            default => 'Toda la tienda',
        };
    }
}
