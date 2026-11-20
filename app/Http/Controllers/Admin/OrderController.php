<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('estado'));

        return view('admin.orders.index', [
            'orders' => Order::withCount('items')
                ->with('items')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'counts' => Order::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load(['items', 'subscriptions.service'])]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($data);

        $message = "Orden {$order->number()}: {$order->status->label()}.";

        if ($order->isPaid()) {
            $created = $order->createSubscriptions();
            if ($created > 0) {
                $message .= " Se agregaron {$created} ".($created === 1 ? 'cuenta' : 'cuentas').' en «Cuentas»: falta ponerles el correo.';
            }
        }

        return back()->with('status', $message);
    }

    public function destroy(Order $order): RedirectResponse
    {
        $number = $order->number();
        $order->delete();

        return redirect()->route('admin.orders.index')->with('status', "Orden {$number} eliminada.");
    }
}
