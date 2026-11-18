<?php

namespace App\Support;

/**
 * Números de WhatsApp guardados solo con dígitos (573001234567).
 */
class Phone
{
    /** 573001234567 → +57 300 123 4567 · 50769588815 → +507 6958-8815 (otros: +número). */
    public static function format(?string $digits): ?string
    {
        if (blank($digits)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/^57(\d{3})(\d{3})(\d{4})$/', $digits, $parts) => "+57 {$parts[1]} {$parts[2]} {$parts[3]}",
            (bool) preg_match('/^507(\d{4})(\d{4})$/', $digits, $parts) => "+507 {$parts[1]}-{$parts[2]}",
            default => '+'.$digits,
        };
    }

    /** Solo dígitos; un celular colombiano de 10 dígitos recibe el indicativo 57. */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) === 10 && str_starts_with($digits, '3') ? '57'.$digits : $digits;
    }
}
