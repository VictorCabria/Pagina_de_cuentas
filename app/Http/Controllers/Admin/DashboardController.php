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
use App\Support\Finances;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ExchangeRate $exchangeRate, Finances $finances): View
    {
        $currency = Currency::from(Setting::get('default_currency'));
        $reportCurrency = Currency::tryFrom((string) $request->query('moneda')) ?? $currency;

        return view('admin.dashboard', [
            'stats' => [
                'Órdenes pendientes' => Order::where('status', OrderStatus::Pending)->count(),
                'Cuentas sin correo' => Subscription::missingAccount()->count(),
                'Vencen en '.Subscription::EXPIRING_DAYS.' días' => Subscription::expiringSoon()->count(),
                'Agotados' => Service::where('is_available', false)->count(),
            ],
            'rate' => $exchangeRate->current(),
            'exchangeRate' => $exchangeRate,
            'currency' => $currency,
            'reportCurrency' => $reportCurrency,
            'totals' => $finances->totals($reportCurrency),
            'thisMonth' => $finances->totals($reportCurrency, now()),
            'chart' => $finances->chart($reportCurrency),
            'soldOut' => Service::where('is_available', false)->ordered()->get(),
            'unavailablePlans' => Plan::with('service')->where('is_available', false)->get(),
        ]);
    }
}
