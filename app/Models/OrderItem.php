<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['plan_id', 'combo_id', 'service_name', 'plan_name', 'duration_months', 'quantity', 'unit_price', 'original_price', 'discount'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'discount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function subtotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    public function durationLabel(): string
    {
        return (new Plan(['duration_months' => $this->duration_months]))->durationLabel();
    }

    /** "Netflix – Perfil individual (3 meses) x2" */
    public function summary(): string
    {
        return "{$this->service_name} – {$this->plan_name} ({$this->durationLabel()})"
            .($this->quantity > 1 ? " x{$this->quantity}" : '');
    }
}
