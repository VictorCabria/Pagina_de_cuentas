<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(ExchangeRate $exchangeRate): View
    {
        return view('admin.settings', [
            'settings' => collect(Setting::DEFAULTS)->map(fn ($_, $key) => Setting::get($key)),
            'exchangeRate' => $exchangeRate,
        ]);
    }

    public function update(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:60'],
            'rate_mode' => ['required', Rule::in(['auto', 'manual'])],
            'usd_to_cop' => ['exclude_unless:rate_mode,manual', 'required', 'numeric', 'min:1', 'max:100000'],
            'default_currency' => ['required', Rule::enum(Currency::class)],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9 ]{7,20}$/'],
            'alert_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'reminder_time' => ['nullable', 'date_format:H:i'],
            'reminder_template' => ['nullable', 'string', 'max:1500'],
        ], [
            'whatsapp.regex' => 'Escribe solo números con el indicativo, p. ej. +57 300 123 4567.',
            'reminder_time.date_format' => 'Escribe la hora como 18:00.',
        ]);

        $data['whatsapp'] = preg_replace('/\D/', '', $data['whatsapp'] ?? '');
        $data['alert_days'] = (string) ($data['alert_days'] ?? 3);
        $data['reminder_time'] ??= Setting::DEFAULTS['reminder_time'];
        // Vacía = vuelve al mensaje original.
        $data['reminder_template'] = filled($data['reminder_template'] ?? null)
            ? str_replace("\r\n", "\n", trim($data['reminder_template']))
            : Setting::DEFAULTS['reminder_template'];

        Setting::put($data);

        if ($data['rate_mode'] === 'auto') {
            $exchangeRate->refresh();
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Ajustes guardados.');
    }

    /** Pide la tasa del mercado en este momento, sin esperar a la próxima hora. */
    public function refreshRate(ExchangeRate $exchangeRate): RedirectResponse
    {
        $status = $exchangeRate->refresh()
            ? 'Tasa actualizada: 1 USD = '.Currency::COP->format($exchangeRate->current()).' COP.'
            : 'No se pudo consultar la tasa ahora mismo. Se mantiene la última conocida.';

        return redirect()->route('admin.settings.edit')->with('status', $status);
    }
}
