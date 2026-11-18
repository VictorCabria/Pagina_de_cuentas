<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use Illuminate\Http\RedirectResponse;

class CurrencyController extends Controller
{
    /** Guarda la moneda que eligió el visitante y lo devuelve a donde estaba. */
    public function __invoke(Currency $currency): RedirectResponse
    {
        session(['currency' => $currency->value]);

        return back(fallback: route('home'));
    }
}
