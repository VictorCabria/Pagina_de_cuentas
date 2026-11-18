<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Support\ExchangeRate;

class DashboardController extends Controller
{
    public function __invoke(ExchangeRate $exchangeRate)
    {
        return view('admin.dashboard', [
            'stats' => [
                'Órdenes pendientes' => Order::where('status', OrderStatus::Pending)->count(),
                'Cuentas sin correo' => Subscription::missingAccount()->count(),
                'Vencen en '.Subscription::EXPIRING_DAYS.' días' => Subscription::expiringSoon()->count(),
                'Agotados' => Service::where('is_available', false)->count(),
            ],
            'rate' => $exchangeRate->current(),
            'exchangeRate' => $exchangeRate,
            'currency' => Currency::from(Setting::get('default_currency')),
            'soldOut' => Service::where('is_available', false)->ordered()->get(),
            'unavailablePlans' => Plan::with('service')->where('is_available', false)->get(),
        ]);
    }
}
