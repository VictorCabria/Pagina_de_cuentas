<?php

namespace App\Support;

/**
 * Datos sensibles enmascarados para listados: el dato real solo se entrega
 * cuando se pide con "Mostrar" (nunca viaja en el HTML de la página).
 */
class Mask
{
    /** Marcador para claves y PIN: no revela ni el largo. */
    public const SECRET = '••••••';

    /** fiera152022@gmail.com → fi*********@gmail.com */
    public static function email(?string $email): ?string
    {
        return blank($email) ? $email : self::emails($email);
    }

    /** Enmascara todos los correos dentro de un texto y deja el resto igual. */
    public static function emails(?string $text): ?string
    {
        if (blank($text)) {
            return $text;
        }

        return preg_replace_callback(
            '/([^\s@]{1,2})([^\s@]*)(@[^\s@]+)/u',
            fn (array $match): string => $match[1].str_repeat('*', mb_strlen($match[2])).$match[3],
            $text,
        );
    }
}
