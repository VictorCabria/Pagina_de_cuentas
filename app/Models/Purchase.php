<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta comprada a un proveedor (la inversión). Ej.: "Netflix 4 pantallas, US$12".
 */
#[Fillable(['service_id', 'description', 'amount', 'currency', 'purchased_on', 'notes'])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency' => Currency::class,
            'purchased_on' => 'date',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Plataforma comprada, o la descripción si es otro gasto. */
    public function label(): string
    {
        return $this->service?->name ?? $this->description ?? 'Otro gasto';
    }

    public function formattedAmount(): string
    {
        return $this->currency->format((float) $this->amount);
    }
}
