<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Combo;
use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Carrito del visitante, guardado en la sesión como ["plan-5" => 2, "combo-1" => 1].
 * Los precios nunca se guardan: se recalculan al mostrarlo y al crear la orden.
 */
class Cart
{
    public const MAX_QUANTITY = 5;

    private const SESSION_KEY = 'cart';

    public function add(Sellable $item, int $quantity = 1): void
    {
        $quantities = $this->quantities();
        $key = $item->cartKey();
        $quantities[$key] = min(self::MAX_QUANTITY, ($quantities[$key] ?? 0) + $quantity);

        $this->save($quantities);
    }

    public function update(Sellable $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($item);

            return;
        }

        $quantities = $this->quantities();
        $quantities[$item->cartKey()] = min(self::MAX_QUANTITY, $quantity);

        $this->save($quantities);
    }

    public function remove(Sellable $item): void
    {
        $quantities = $this->quantities();
        unset($quantities[$item->cartKey()]);

        $this->save($quantities);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    /** Busca un plan o combo por su clave del carrito ("plan-5", "combo-2"). */
    public function find(string $key): ?Sellable
    {
        if (! preg_match('/^(plan|combo)-(\d+)$/', $key, $match)) {
            return null;
        }

        return $match[1] === 'plan'
            ? Plan::with('service')->find($match[2])
            : Combo::with('plans.service')->find($match[2]);
    }

    /**
     * Líneas con el precio actual en la moneda pedida, en el orden en que se agregaron.
     * Ignora lo que ya no existe o se ocultó de la tienda.
     *
     * @return Collection<int, CartLine>
     */
    public function lines(Currency $currency): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        $ids = ['plan' => [], 'combo' => []];
        foreach (array_keys($quantities) as $key) {
            [$type, $id] = explode('-', $key, 2);
            $ids[$type][] = (int) $id;
        }

        /** @var Collection<string, Sellable> $items */
        $items = collect([
            ...Plan::with('service.category')->whereKey($ids['plan'])->get()->filter(fn (Plan $plan) => $plan->service->is_active),
            ...Combo::with('plans.service')->whereKey($ids['combo'])->active()->get(),
        ])->keyBy(fn (Sellable $item) => $item->cartKey());

        return collect($quantities)
            ->filter(fn (int $quantity, string $key) => $items->has($key))
            ->map(fn (int $quantity, string $key) => new CartLine($items[$key], $items[$key]->price($currency), $quantity))
            ->values();
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function total(Collection $lines): float
    {
        return $lines->filter->isPurchasable()->sum(fn (CartLine $line) => $line->subtotal());
    }

    /**
     * Las claves numéricas vienen de carritos guardados antes de existir los combos.
     *
     * @return array<string, int>
     */
    private function quantities(): array
    {
        $quantities = [];
        foreach (session(self::SESSION_KEY, []) as $key => $quantity) {
            $key = is_int($key) ? 'plan-'.$key : $key;
            if (preg_match('/^(plan|combo)-\d+$/', $key)) {
                $quantities[$key] = (int) $quantity;
            }
        }

        return $quantities;
    }

    /**
     * @param  array<string, int>  $quantities
     */
    private function save(array $quantities): void
    {
        session([self::SESSION_KEY => $quantities]);
    }
}
