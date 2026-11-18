<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagada',
            self::Delivered => 'Entregada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** Color de la etiqueta en el panel (ver x-badge). */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'blue',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }
}
