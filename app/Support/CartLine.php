<?php

namespace App\Support;

/**
 * Un plan o combo dentro del carrito con su precio actual.
 */
final readonly class CartLine
{
    public function __construct(
        public Sellable $item,
        public Price $price,
        public int $quantity,
    ) {}

    public function subtotal(): float
    {
        return $this->price->final * $this->quantity;
    }

    public function isPurchasable(): bool
    {
        return $this->item->purchasable();
    }
}
