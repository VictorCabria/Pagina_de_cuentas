<?php

namespace App\Models;

use App\Enums\Currency;
use Carbon\CarbonInterface;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta comprada a un proveedor (la inversión). Ej.: "Netflix 4 pantallas, US$12".
 */
#[Fillable(['service_id', 'description', 'amount', 'currency', 'purchased_on', 'duration_months', 'expires_on', 'notes'])]
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
            'duration_months' => 'integer',
            'expires_on' => 'date',
        ];
    }

    /**
     * Fecha de compra + meses, sin pasarse de mes: 20 nov + 6 = 20 may; 31 ene + 1 = 28 feb.
     */
    public static function expiryFor(CarbonInterface $purchasedOn, ?int $months): ?CarbonInterface
    {
        return $months ? $purchasedOn->copy()->addMonthsNoOverflow($months) : null;
    }

    /** Días que faltan para que venza (negativo si ya venció); null si no vence. */
    public function daysLeft(): ?int
    {
        return $this->expires_on ? (int) today()->diffInDays($this->expires_on, false) : null;
    }

    public function durationLabel(): ?string
    {
        return match (true) {
            ! $this->duration_months => null,
            $this->duration_months === 1 => '1 mes',
            $this->duration_months === 12 => '1 año',
            default => $this->duration_months.' meses',
        };
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
