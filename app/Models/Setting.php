<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Valores por defecto mientras no se guarden en el panel. */
    public const DEFAULTS = [
        'store_name' => 'CuentaPlay',
        'usd_to_cop' => '4000',
        'rate_mode' => 'auto',
        'rate_source' => '',
        'rate_updated_at' => '',
        'default_currency' => 'COP',
        'whatsapp' => '',
        'alert_days' => '3',
        'reminder_time' => '18:00',
        'reminder_template' => "Hola {nombre}. Le recordamos que su perfil {plataforma} {perfil} vence en {dias} días, el {fecha} a las {hora}.\n\n✅ Si continúa, favor realizar el pago ANTES de la FECHA/HORA de corte.\n✅ Si NO continúa, agradecemos informarlo.\n\nPor favor confirmar. Gracias.",
    ];

    public static function get(string $key): ?string
    {
        $stored = Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());

        return filled($stored[$key] ?? null) ? $stored[$key] : (static::DEFAULTS[$key] ?? null);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }
}
