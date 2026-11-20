<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Finances;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /** Cuadro detallado: totales, por mes, por plataforma y cada movimiento. */
    public function __invoke(Request $request, Finances $finances): View
    {
        $currency = Currency::tryFrom((string) $request->query('moneda')) ?? Currency::from(Setting::get('default_currency'));

        return view('admin.finances', [
            'currency' => $currency,
            'finances' => $finances,
            'totals' => $finances->totals($currency),
            'monthly' => $finances->monthly($currency),
            'products' => $finances->byProduct($currency),
            'orders' => $finances->paidOrders(),
            'purchases' => $finances->purchases(),
            'chart' => $finances->chart($currency),
        ]);
    }
}
