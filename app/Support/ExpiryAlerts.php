<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Busca las cuentas que vencen pronto y deja el aviso en el panel (campana y alerta).
 * Desde ahí el admin le escribe al cliente por WhatsApp con un clic.
 * Cada vencimiento se avisa una sola vez.
 */
class ExpiryAlerts
{
    /** Marca para no revisar en cada petición del panel. */
    private const THROTTLE_KEY = 'expiry-alerts.checked';

    public function days(): int
    {
        return max(0, (int) (Setting::get('alert_days') ?? 3));
    }

    /** Cuentas que vencen desde hoy hasta dentro de los días de aviso. */
    public function expiringCount(): int
    {
        return Subscription::query()
            ->whereDate('expires_on', '>=', today())
            ->whereDate('expires_on', '<=', today()->addDays($this->days()))
            ->count();
    }

    /**
     * Revisa como mucho una vez por hora (se llama en cada carga del panel).
     */
    public function runIfDue(): void
    {
        if (Cache::add(self::THROTTLE_KEY, true, now()->addHour())) {
            $this->run();
        }
    }

    /**
     * @return Collection<int, Subscription> las cuentas avisadas en esta corrida
     */
    public function run(): Collection
    {
        $due = Subscription::with('service')
            ->whereDate('expires_on', '>=', today())
            ->whereDate('expires_on', '<=', today()->addDays($this->days()))
            ->where(fn ($query) => $query->whereNull('expiry_alerted_for')->orWhereColumn('expiry_alerted_for', '!=', 'expires_on'))
            ->orderBy('expires_on')
            ->get();

        if ($due->isEmpty()) {
            return $due;
        }

        $admins = User::all();
        foreach ($due as $subscription) {
            Notification::send($admins, new SubscriptionExpiring($subscription));
            $subscription->forceFill(['expiry_alerted_for' => $subscription->expires_on])->save();
        }

        return $due;
    }
}
