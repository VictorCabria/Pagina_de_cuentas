<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Models\Combo;
use App\Models\Setting;
use App\Support\Cart;
use App\Support\CartLine;
use App\Support\Sellable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        $currency = Currency::current();
        $lines = $cart->lines($currency);

        return view('store.cart', [
            'currency' => $currency,
            'lines' => $lines,
            'total' => $cart->total($lines),
            'hasUnavailable' => $lines->contains(fn ($line) => ! $line->isPurchasable()),
            'canOrder' => filled(Setting::get('whatsapp')),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required_without:combo_id', 'nullable', 'integer', 'exists:plans,id'],
            'combo_id' => ['nullable', 'integer', 'exists:combos,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
        ], [
            'plan_id.required_without' => 'Elige un plan.',
        ]);

        $item = filled($data['combo_id'] ?? null)
            ? $cart->find('combo-'.$data['combo_id'])
            : $cart->find('plan-'.$data['plan_id']);

        if (! $item->purchasable()) {
            return back()->with('cart_error', $item instanceof Combo
                ? 'Ese combo está agotado en este momento.'
                : 'Ese plan está agotado en este momento.');
        }

        $cart->add($item, $data['quantity'] ?? 1);

        if ($request->boolean('checkout')) {
            return redirect()->route('cart.show');
        }

        return back()->with('cart_added', "{$item->cartLabel()} se agregó al carrito.");
    }

    public function update(Request $request, string $item, Cart $cart): RedirectResponse|JsonResponse
    {
        $sellable = $cart->find($item) ?? abort(404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.Cart::MAX_QUANTITY],
        ]);

        $cart->update($sellable, $data['quantity']);

        return $request->expectsJson() ? $this->summary($cart, $sellable) : redirect()->route('cart.show');
    }

    public function destroy(Request $request, string $item, Cart $cart): RedirectResponse|JsonResponse
    {
        $sellable = $cart->find($item) ?? abort(404);

        $cart->remove($sellable);

        return $request->expectsJson() ? $this->summary($cart, $sellable) : redirect()->route('cart.show');
    }

    /**
     * Lo que el carrito necesita para actualizarse sin recargar la página
     * (y sin borrar lo que el cliente ya escribió).
     */
    private function summary(Cart $cart, Sellable $item): JsonResponse
    {
        $currency = Currency::current();
        $lines = $cart->lines($currency);
        $line = $lines->first(fn (CartLine $line) => $line->item->cartKey() === $item->cartKey());

        return response()->json([
            'quantity' => $line?->quantity ?? 0,
            'max' => Cart::MAX_QUANTITY,
            'subtotal' => $line ? $currency->format($line->subtotal()) : null,
            'original' => $line?->price->hasDiscount() ? $currency->format($line->price->original * $line->quantity) : null,
            'total' => $currency->format($cart->total($lines)),
            'count' => $cart->count(),
            'canOrder' => $lines->contains->isPurchasable(),
        ]);
    }
}
