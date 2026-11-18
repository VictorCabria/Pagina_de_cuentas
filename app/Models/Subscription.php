<?php

namespace App\Models;

use App\Support\Phone;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cuenta entregada a un cliente: plataforma, datos de acceso y vigencia.
 */
#[Fillable([
    'service_id', 'order_id', 'customer_name', 'customer_phone', 'account_email',
    'account_password', 'profile_name', 'pin', 'starts_on', 'expires_on', 'notes',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /** Días antes del vencimiento en que se marca "Vence pronto". */
    public const EXPIRING_DAYS = 5;

    protected function casts(): array
    {
        return [
            'account_password' => 'encrypted',
            'starts_on' => 'date',
            'expires_on' => 'date',
            'expiry_alerted_for' => 'date',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Vendidas en la tienda a las que todavía no se les puso el correo. */
    public function scopeMissingAccount(Builder $query): void
    {
        $query->whereNull('account_email')->whereDate('expires_on', '>=', today());
    }

    public function scopeExpiringSoon(Builder $query): void
    {
        $query->whereDate('expires_on', '>=', today())->whereDate('expires_on', '<=', today()->addDays(self::EXPIRING_DAYS));
    }

    public function scopeExpired(Builder $query): void
    {
        $query->whereDate('expires_on', '<', today());
    }

    /**
     * @return array{label: string, color: string}
     */
    public function status(): array
    {
        return match (true) {
            $this->expires_on->lt(today()) => ['label' => 'Vencida', 'color' => 'red'],
            blank($this->account_email) => ['label' => 'Falta correo', 'color' => 'amber'],
            $this->expires_on->lte(today()->addDays(self::EXPIRING_DAYS)) => ['label' => 'Vence pronto', 'color' => 'pink'],
            default => ['label' => 'Activa', 'color' => 'green'],
        };
    }

    public function daysLeft(): int
    {
        return (int) today()->diffInDays($this->expires_on, false);
    }

    /** "Netflix de Laura (perfil PIXEL) vence mañana, 18 nov." */
    public function expiryText(): string
    {
        $days = $this->daysLeft();
        $when = match (true) {
            $days < 0 => 'venció el',
            $days === 0 => 'vence HOY,',
            $days === 1 => 'vence mañana,',
            default => "vence en {$days} días,",
        };

        return "{$this->service->name} de {$this->customer_name}"
            .($this->profile_name ? " (perfil {$this->profile_name})" : '')
            ." {$when} ".$this->expires_on->locale('es')->isoFormat('D MMM');
    }

    public function formattedPhone(): ?string
    {
        return Phone::format($this->customer_phone);
    }

    /** Mensaje con los datos de acceso para enviarle al cliente. */
    public function credentialsMessage(): string
    {
        $lines = [
            "Hola {$this->customer_name} 👋, estos son los datos de tu cuenta de *{$this->service->name}*:",
            '',
            "📧 Correo: {$this->account_email}",
        ];

        if (filled($this->account_password)) {
            $lines[] = "🔑 Contraseña: {$this->account_password}";
        }
        if (filled($this->profile_name)) {
            $lines[] = "👤 Tu perfil: {$this->profile_name}";
        }
        if (filled($this->pin)) {
            $lines[] = "🔢 PIN de tu perfil: {$this->pin}";
        }

        $lines[] = "📅 Vence el {$this->expires_on->locale('es')->isoFormat('D [de] MMMM [de] YYYY')}";
        $lines[] = '';
        $lines[] = 'Por favor no cambies la contraseña ni los datos de la cuenta. ¡Gracias por tu compra!';

        return implode("\n", $lines);
    }

    /**
     * Se le debe recordar al cliente: le faltan los días de aviso o menos, o ya venció.
     */
    public function needsReminder(): bool
    {
        return filled($this->customer_phone)
            && $this->daysLeft() <= (int) Setting::get('alert_days');
    }

    /**
     * Recordatorio de vencimiento para el cliente, con la plantilla de Ajustes.
     * Marcadores: {nombre} {plataforma} {perfil} {dias} {fecha} {hora}.
     */
    public function reminderMessage(): string
    {
        $cutoff = Carbon::createFromFormat('H:i', Setting::get('reminder_time') ?: '18:00');

        $message = strtr(Setting::get('reminder_template'), [
            '{nombre}' => $this->customer_name,
            '{plataforma}' => mb_strtoupper($this->service->name),
            '{perfil}' => (string) $this->profile_name,
            '{dias}' => (string) max(0, $this->daysLeft()),
            '{fecha}' => $this->expires_on->locale('es')->isoFormat('dddd, D [de] MMMM'),
            '{hora}' => $cutoff->format('g.i').$cutoff->format('a'),
        ]);

        // Si no hay perfil, no deja espacios dobles ("su perfil NETFLIX  vence").
        return preg_replace('/[ \t]{2,}/', ' ', $message);
    }

    public function reminderUrl(): ?string
    {
        return filled($this->customer_phone)
            ? 'https://wa.me/'.$this->customer_phone.'?text='.rawurlencode($this->reminderMessage())
            : null;
    }
}
