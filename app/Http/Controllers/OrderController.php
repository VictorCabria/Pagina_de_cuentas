<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Support\Cart;
use App\Support\CartLine;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Convierte el carrito en una orden y lleva al cliente a la confirmación,
     * desde donde la envía por WhatsApp.
     */
    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:80'],
            'customer_phone' => ['required', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
        ], [
            'customer_name.required' => 'Escribe tu nombre.',
            'customer_phone.required' => 'Escribe tu número de WhatsApp.',
            'customer_phone.regex' => 'Escribe un número válido, p. ej. 300 123 4567.',
        ]);

        if (blank(Setting::get('whatsapp'))) {
            return back()->with('cart_error', 'Los pedidos por WhatsApp aún no están habilitados.');
        }

        $currency = Currency::current();
        $lines = $cart->lines($currency);

        if ($lines->isEmpty()) {
            return redirect()->route('cart.show')->with('cart_error', 'Tu carrito está vacío.');
        }

        // Si algo se agotó mientras tanto, se quita y el cliente revisa antes de enviar.
        $unavailable = $lines->reject->isPurchasable();
        if ($unavailable->isNotEmpty()) {
            $unavailable->each(fn (CartLine $line) => $cart->remove($line->item));

            return redirect()->route('cart.show')->withInput()
                ->with('cart_error', 'Quitamos del carrito lo que se agotó. Revisa tu pedido y vuelve a enviarlo.');
        }

        $order = DB::transaction(function () use ($data, $currency, $lines, $cart) {
            $order = Order::create([
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => Phone::normalize($data['customer_phone']),
                'currency' => $currency,
                'total' => $cart->total($lines),
            ]);

            $order->items()->createMany($lines->map(fn (CartLine $line) => [
                'plan_id' => $line->item instanceof Plan ? $line->item->id : null,
                'combo_id' => $line->item instanceof Combo ? $line->item->id : null,
                'service_name' => $line->item->cartTitle(),
                'plan_name' => $line->item instanceof Plan ? $line->item->name : 'Incluye '.$line->item->includedLabel(),
                'duration_months' => $line->item->duration_months,
                'quantity' => $line->quantity,
                'unit_price' => $line->price->final,
                'original_price' => $line->price->original,
                'discount' => $line->price->discount,
            ])->all());

            return $order;
        });

        $cart->clear();

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order): View
    {
        $order->load('items');

        return view('store.order', [
            'order' => $order,
            'currency' => $order->currency,
            'whatsappUrl' => $order->whatsappUrl(Setting::get('whatsapp')),
        ]);
    }
}
