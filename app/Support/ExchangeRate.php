<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Tasa USD → COP.
 *
 * En modo automático usa la tasa de mercado en vivo (la misma que muestra Google)
 * y la refresca como mucho una vez por hora. Si ninguna fuente responde se sigue
 * usando la última tasa guardada. En modo manual usa la que se escribe en el panel.
 */
class ExchangeRate
{
    /** Marca de "ya se consultó hace poco" para no llamar a las fuentes en cada petición. */
    public const CHECKED_KEY = 'exchange_rate.checked';

    private const REFRESH_MINUTES = 60;

    private const RETRY_MINUTES = 5;

    /**
     * Fuentes en orden de preferencia y la ruta del valor dentro del JSON.
     *
     * @var array<string, array{url: string, path: string}>
     */
    private const SOURCES = [
        'Yahoo Finance' => [
            'url' => 'https://query1.finance.yahoo.com/v8/finance/chart/COP=X?interval=1d&range=1d',
            'path' => 'chart.result.0.meta.regularMarketPrice',
        ],
        'Currency API' => [
            'url' => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.min.json',
            'path' => 'usd.cop',
        ],
    ];

    public function isAutomatic(): bool
    {
        return Setting::get('rate_mode') !== 'manual';
    }

    public function current(): float
    {
        if ($this->isAutomatic() && ! Cache::has(self::CHECKED_KEY)) {
            $this->refresh();
        }

        return max(0.0, (float) Setting::get('usd_to_cop'));
    }

    /**
     * Consulta las fuentes y guarda la tasa. Devuelve false si ninguna respondió.
     */
    public function refresh(): bool
    {
        $fetched = $this->fetch();

        Cache::put(self::CHECKED_KEY, true, now()->addMinutes($fetched ? self::REFRESH_MINUTES : self::RETRY_MINUTES));

        if ($fetched === null) {
            return false;
        }

        Setting::put([
            'usd_to_cop' => (string) round($fetched['rate'], 2),
            'rate_source' => $fetched['source'],
            'rate_updated_at' => now()->toIso8601String(),
        ]);

        return true;
    }

    public function source(): ?string
    {
        return $this->isAutomatic() ? Setting::get('rate_source') : null;
    }

    public function updatedAt(): ?Carbon
    {
        $value = Setting::get('rate_updated_at');

        return $this->isAutomatic() && filled($value) ? Carbon::parse($value) : null;
    }

    /**
     * @return array{rate: float, source: string}|null
     */
    private function fetch(): ?array
    {
        foreach (self::SOURCES as $source => $config) {
            try {
                $rate = data_get(
                    Http::timeout(4)->withUserAgent('Mozilla/5.0')->get($config['url'])->throw()->json(),
                    $config['path'],
                );
            } catch (Throwable) {
                continue;
            }

            // Descarta respuestas absurdas antes de cambiar todos los precios.
            if (is_numeric($rate) && $rate > 100 && $rate < 100000) {
                return ['rate' => (float) $rate, 'source' => $source];
            }
        }

        return null;
    }
}
