<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Notification;

/**
 * Aviso en el panel: una cuenta de un cliente está por vencer.
 */
class SubscriptionExpiring extends Notification
{
    public function __construct(public Subscription $subscription) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subscription = $this->subscription;

        return [
            'subscription_id' => $subscription->id,
            'service_id' => $subscription->service_id,
            'service' => $subscription->service->name,
            'customer' => $subscription->customer_name,
            'profile' => $subscription->profile_name,
            'expires_on' => $subscription->expires_on->toDateString(),
            'message' => $subscription->expiryText(),
        ];
    }
}
