<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Support\Phone;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido enviado por WhatsApp. El pago y la entrega se coordinan en el chat.
 */
#[Fillable(['customer_name', 'customer_phone', 'currency', 'total', 'status', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUlids;

    /** Los números de orden arrancan en #1001. */
    private const NUMBER_OFFSET = 1000;

    protected $attributes = [
        'status' => 'pending',
    ];

    /** Estados que cuentan como venta cobrada. */
    public const PAID_STATUSES = [OrderStatus::Paid, OrderStatus::Delivered];

    protected static function booted(): void
    {
        // Guarda cuándo se cobró la primera vez que pasa a Pagada o Entregada.
        static::saving(function (Order $order) {
            if ($order->paid_at === null && $order->isPaid()) {
                $order->paid_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** Órdenes cobradas: pagadas o ya entregadas. */
    public function scopePaid(Builder $query): void
    {
        $query->whereIn('status', self::PAID_STATUSES);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }

    /**
     * El ULID solo se usa en el token público; la llave sigue siendo el id.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['token'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function number(): string
    {
        return '#'.($this->id + self::NUMBER_OFFSET);
    }

    public function formattedTotal(): string
    {
        return $this->currency->format((float) $this->total);
    }

    public function formattedPhone(): string
    {
        return Phone::format($this->customer_phone);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Al quedar pagada, deja una fila en "Cuentas" por cada cuenta vendida
     * (un combo cuenta una por plataforma) con el cliente y las fechas ya puestas.
     * El correo, la clave y el PIN los completa el admin. No duplica si ya se crearon.
     */
    public function createSubscriptions(): int
    {
        if ($this->subscriptions()->exists()) {
            return 0;
        }

        $this->loadMissing(['items.plan', 'items.combo.plans']);
        $created = 0;

        foreach ($this->items as $item) {
            $serviceIds = match (true) {
                $item->plan !== null => [$item->plan->service_id],
                $item->combo !== null => $item->combo->plans->pluck('service_id')->unique()->all(),
                default => [],
            };

            foreach ($serviceIds as $serviceId) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $this->subscriptions()->create([
                        'service_id' => $serviceId,
                        'customer_name' => $this->customer_name,
                        'customer_phone' => $this->customer_phone,
                        'starts_on' => today(),
                        'expires_on' => today()->addMonthsNoOverflow($item->duration_months),
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }

    /** Texto que el cliente envía a la tienda por WhatsApp. */
    public function whatsappMessage(): string
    {
        $lines = ["🛒 *Nuevo pedido {$this->number()}*", '', "👤 {$this->customer_name}", "📱 {$this->formattedPhone()}", ''];

        foreach ($this->items as $item) {
            $lines[] = "• {$item->summary()}: *{$this->currency->format($item->subtotal())}*"
                .($item->discount > 0 ? " (-{$item->discount}%)" : '');
        }

        $lines[] = '';
        $lines[] = "💰 *Total: {$this->formattedTotal()} {$this->currency->value}*";
        $lines[] = '';
        $lines[] = 'Quedo atento(a) a los datos de pago. ¡Gracias!';

        return implode("\n", $lines);
    }

    public function whatsappUrl(string $storeNumber): string
    {
        return 'https://wa.me/'.$storeNumber.'?text='.rawurlencode($this->whatsappMessage());
    }
}
