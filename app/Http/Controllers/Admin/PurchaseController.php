<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Finances;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request, Finances $finances): View
    {
        $currency = Currency::tryFrom((string) $request->query('moneda')) ?? Currency::from(Setting::get('default_currency'));
        $purchases = Purchase::with('service')->latest('purchased_on')->latest('id')->get();

        return view('admin.purchases.index', [
            'purchases' => $purchases,
            'currency' => $currency,
            'finances' => $finances,
            'total' => $finances->totals($currency)['investment'],
            'thisMonth' => $finances->totals($currency, now())['investment'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.purchases.form', $this->formData(new Purchase([
            'service_id' => $request->integer('servicio') ?: null,
            'currency' => Currency::from(Setting::get('default_currency')),
            'purchased_on' => today(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $purchase = Purchase::create($this->validated($request));

        return redirect()->route('admin.purchases.index')
            ->with('status', "Compra de {$purchase->label()} por {$purchase->formattedAmount()} anotada.");
    }

    public function edit(Purchase $purchase): View
    {
        return view('admin.purchases.form', $this->formData($purchase));
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $purchase->update($this->validated($request));

        return redirect()->route('admin.purchases.index')->with('status', "Compra de {$purchase->label()} actualizada.");
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $purchase->delete();

        return back()->with('status', "Compra de {$purchase->label()} eliminada.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Purchase $purchase): array
    {
        return [
            'purchase' => $purchase,
            'services' => Service::ordered()->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'required_without:description', 'exists:services,id'],
            'description' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'purchased_on' => ['required', 'date'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'service_id.required_without' => 'Elige la plataforma o escribe qué compraste.',
            'amount.gt' => 'El monto debe ser mayor que cero.',
            'duration_months.min' => 'La cuenta debe durar al menos 1 mes.',
            'duration_months.max' => 'Como máximo 36 meses.',
        ]);

        // Sin plataforma elegida es "otro gasto": se borra la que tuviera antes.
        $data['service_id'] ??= null;
        $data['duration_months'] = isset($data['duration_months']) ? (int) $data['duration_months'] : null;
        $data['expires_on'] = Purchase::expiryFor(Carbon::parse($data['purchased_on']), $data['duration_months']);

        return $data;
    }
}
