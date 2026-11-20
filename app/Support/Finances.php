<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cuentas del negocio: lo ganado (órdenes pagadas o entregadas) y lo invertido
 * (las compras de cuentas anotadas en Inversión), todo pasado a una sola moneda con la tasa actual.
 */
class Finances
{
    private ?float $rate = null;

    /** @var Collection<int, Order>|null */
    private ?Collection $paidOrders = null;

    /** @var Collection<int, Purchase>|null */
    private ?Collection $purchases = null;

    public function __construct(private ExchangeRate $exchangeRate) {}

    public function convert(float $amount, Currency $from, Currency $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $this->rate ??= $this->exchangeRate->current();

        if ($this->rate <= 0) {
            return 0.0;
        }

        return $from === Currency::USD ? $amount * $this->rate : $amount / $this->rate;
    }

    /**
     * Totales de todo el tiempo o, si se pasa un mes, solo de ese mes.
     *
     * @return array{income: float, investment: float, profit: float, orders: int, purchases: int}
     */
    public function totals(Currency $currency, ?Carbon $month = null): array
    {
        $orders = $this->paidOrders()->when($month, fn (Collection $orders) => $orders->filter(
            fn (Order $order) => $order->paid_at->isSameMonth($month),
        ));
        $purchases = $this->purchases()->when($month, fn (Collection $purchases) => $purchases->filter(
            fn (Purchase $purchase) => $purchase->purchased_on->isSameMonth($month),
        ));

        return $this->summarize($currency, $orders, $purchases);
    }

    /**
     * Un renglón por mes con movimiento, del más reciente al más antiguo.
     *
     * @return Collection<string, array{month: Carbon, income: float, investment: float, profit: float, orders: int, purchases: int}>
     */
    public function monthly(Currency $currency): Collection
    {
        $orders = $this->paidOrders()->groupBy(fn (Order $order) => $order->paid_at->format('Y-m'));
        $purchases = $this->purchases()->groupBy(fn (Purchase $purchase) => $purchase->purchased_on->format('Y-m'));

        return $orders->keys()->merge($purchases->keys())->unique()->sortDesc()->values()
            ->mapWithKeys(fn (string $key) => [$key => [
                'month' => Carbon::createFromFormat('!Y-m', $key),
                ...$this->summarize($currency, $orders->get($key, collect()), $purchases->get($key, collect())),
            ]]);
    }

    /**
     * Meses seguidos para la gráfica, del más antiguo al actual, con ceros donde no hubo movimiento.
     * Arranca en el primer mes con datos, pero muestra al menos los últimos 6 y como mucho 12.
     *
     * @return list<array{month: Carbon, income: float, investment: float, profit: float}>
     */
    public function chart(Currency $currency, int $minMonths = 6, int $maxMonths = 12): array
    {
        $monthly = $this->monthly($currency);
        $current = now()->startOfMonth();
        $first = $monthly->isEmpty() ? $current : $monthly->last()['month'];
        $span = min($maxMonths, max($minMonths, (int) $first->diffInMonths($current) + 1));

        $points = [];
        for ($i = $span - 1; $i >= 0; $i--) {
            $month = $current->copy()->subMonthsNoOverflow($i);
            $row = $monthly->get($month->format('Y-m'));
            $points[] = [
                'month' => $month,
                'income' => $row['income'] ?? 0.0,
                'investment' => $row['investment'] ?? 0.0,
                'profit' => $row['profit'] ?? 0.0,
            ];
        }

        return $points;
    }

    /**
     * Ganado e invertido por plataforma (o combo), de mayor a menor ganancia.
     *
     * @return Collection<string, array{name: string, income: float, investment: float, profit: float, sold: int, bought: int}>
     */
    public function byProduct(Currency $currency): Collection
    {
        $rows = [];
        $empty = fn (string $name) => ['name' => $name, 'income' => 0.0, 'investment' => 0.0, 'profit' => 0.0, 'sold' => 0, 'bought' => 0];

        foreach ($this->paidOrders() as $order) {
            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $rows[$item->service_name] ??= $empty($item->service_name);
                $rows[$item->service_name]['income'] += $this->convert($item->subtotal(), $order->currency, $currency);
                $rows[$item->service_name]['sold'] += $item->quantity;
            }
        }

        foreach ($this->purchases() as $purchase) {
            $name = $purchase->label();
            $rows[$name] ??= $empty($name);
            $rows[$name]['investment'] += $this->cost($purchase, $currency);
            $rows[$name]['bought']++;
        }

        return collect($rows)
            ->map(fn (array $row) => [...$row, 'profit' => $row['income'] - $row['investment']])
            ->sortByDesc('profit');
    }

    /**
     * @return Collection<int, Order>
     */
    public function paidOrders(): Collection
    {
        return $this->paidOrders ??= Order::paid()->with('items')->latest('paid_at')->get();
    }

    /**
     * Compras de la más reciente a la más antigua.
     *
     * @return Collection<int, Purchase>
     */
    public function purchases(): Collection
    {
        return $this->purchases ??= Purchase::with('service')->latest('purchased_on')->latest('id')->get();
    }

    public function income(Order $order, Currency $currency): float
    {
        return $this->convert((float) $order->total, $order->currency, $currency);
    }

    public function cost(Purchase $purchase, Currency $currency): float
    {
        return $this->convert((float) $purchase->amount, $purchase->currency, $currency);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  Collection<int, Purchase>  $purchases
     * @return array{income: float, investment: float, profit: float, orders: int, purchases: int}
     */
    private function summarize(Currency $currency, Collection $orders, Collection $purchases): array
    {
        $income = $orders->sum(fn (Order $order) => $this->income($order, $currency));
        $investment = $purchases->sum(fn (Purchase $purchase) => $this->cost($purchase, $currency));

        return [
            'income' => $income,
            'investment' => $investment,
            'profit' => $income - $investment,
            'orders' => $orders->count(),
            'purchases' => $purchases->count(),
        ];
    }
}
