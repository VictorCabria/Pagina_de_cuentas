<?php

namespace App\Support;

use App\Enums\Currency;

/**
 * Algo que se puede poner en el carrito: un plan suelto o un combo.
 */
interface Sellable
{
    /** Identificador en el carrito y en la URL: "plan-5", "combo-2". */
    public function cartKey(): string;

    /** Título de la línea: "Netflix", "Combo Diamante". */
    public function cartTitle(): string;

    /** Detalle de la línea: "Perfil individual · 3 meses". */
    public function cartSubtitle(): string;

    /** Nombre corto para avisos: "Netflix (3 meses)". */
    public function cartLabel(): string;

    public function price(Currency $currency): Price;

    public function purchasable(): bool;

    public function storeUrl(): string;
}
