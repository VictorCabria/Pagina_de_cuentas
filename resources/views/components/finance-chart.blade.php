{{--
    Gráfica de líneas por mes: Ganado (cian, línea continua) e Invertido (rojo, línea punteada).
    Colores validados para daltonismo sobre el fondo de las tarjetas; la línea punteada y las
    etiquetas al final de cada línea evitan depender solo del color.
--}}
@props(['points', 'currency'])

@php
    $width = 720;
    $height = 260;
    $padding = ['top' => 16, 'right' => 84, 'bottom' => 32, 'left' => 72];
    $plotWidth = $width - $padding['left'] - $padding['right'];
    $plotHeight = $height - $padding['top'] - $padding['bottom'];

    $series = [
        'income' => ['label' => 'Ganado', 'color' => '#0ea5c6', 'dash' => null],
        'investment' => ['label' => 'Invertido', 'color' => '#f43f5e', 'dash' => '6 5'],
    ];

    // Marcas "redondas" del eje (1, 2, 2.5 o 5 × 10^n) hasta cubrir el mayor valor, unas 4.
    $peak = max(1, ...array_map(fn ($point) => max($point['income'], $point['investment']), $points));
    $magnitude = 10 ** floor(log10($peak / 4));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($factor) => $factor * $magnitude)->first(fn ($value) => $value * 4 >= $peak);
    $niceMax = $step * ceil($peak / $step);
    $ticks = array_map(fn ($i) => $step * $i, range(0, (int) round($niceMax / $step)));

    // La etiqueta de la línea que termina más arriba va encima; la otra, debajo.
    $last = end($points);
    $labelOffset = [
        'income' => $last['income'] >= $last['investment'] ? -6 : 14,
        'investment' => $last['income'] >= $last['investment'] ? 14 : -6,
    ];

    $count = count($points);
    $x = fn (int $index) => $padding['left'] + ($count > 1 ? $plotWidth * $index / ($count - 1) : $plotWidth / 2);
    $y = fn (float $value) => $padding['top'] + $plotHeight - ($value / $niceMax) * $plotHeight;
    $short = fn (float $value) => match (true) {
        $value >= 1_000_000 => rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' M',
        $value >= 1_000 && $currency === \App\Enums\Currency::COP => rtrim(rtrim(number_format($value / 1_000, 1, ',', '.'), '0'), ',').' mil',
        default => $currency->format($value),
    };
    $columnWidth = $count > 1 ? $plotWidth / ($count - 1) : $plotWidth;
@endphp

<div {{ $attributes->class('card') }} data-finance-chart>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-mist-300">Ganado vs. invertido por mes</h2>
        <ul class="flex gap-4 text-xs text-mist-300" aria-label="Leyenda">
            @foreach ($series as $line)
                <li class="flex items-center gap-2">
                    <svg width="22" height="8" aria-hidden="true"><line x1="1" y1="4" x2="21" y2="4" stroke="{{ $line['color'] }}" stroke-width="2" stroke-linecap="round" @if ($line['dash']) stroke-dasharray="{{ $line['dash'] }}" @endif /></svg>
                    {{ $line['label'] }}
                </li>
            @endforeach
        </ul>
    </div>

    <div class="relative">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full overflow-visible" role="img"
             aria-label="Gráfica de lo ganado y lo invertido en los últimos {{ $count }} meses. El detalle está en la tabla Por mes.">
            {{-- Cuadrícula y eje Y --}}
            @foreach ($ticks as $tick)
                <line x1="{{ $padding['left'] }}" x2="{{ $width - $padding['right'] }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}"
                      stroke="#1e2a4a" stroke-width="1" @if ($tick > 0) stroke-dasharray="2 4" @endif />
                <text x="{{ $padding['left'] - 10 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" class="fill-mist-400 text-[11px] tabular-nums">{{ $short($tick) }}</text>
            @endforeach

            {{-- Meses --}}
            @foreach ($points as $index => $point)
                <text x="{{ $x($index) }}" y="{{ $height - 10 }}" text-anchor="middle" class="fill-mist-400 text-[11px]">
                    {{ Str::ucfirst(rtrim($point['month']->locale('es')->isoFormat('MMM'), '.')) }}{{ $point['month']->month === 1 || $index === 0 ? ' '.$point['month']->format('y') : '' }}
                </text>
            @endforeach

            {{-- Línea guía del hover --}}
            <line data-crosshair x1="0" x2="0" y1="{{ $padding['top'] }}" y2="{{ $padding['top'] + $plotHeight }}" stroke="#2c3a63" stroke-width="1" class="hidden" />

            {{-- Líneas, puntos y etiqueta al final --}}
            @foreach ($series as $key => $line)
                <polyline fill="none" stroke="{{ $line['color'] }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
                          @if ($line['dash']) stroke-dasharray="{{ $line['dash'] }}" @endif
                          points="{{ collect($points)->map(fn ($point, $index) => round($x($index), 1).','.round($y($point[$key]), 1))->join(' ') }}" />
                @foreach ($points as $index => $point)
                    <circle cx="{{ $x($index) }}" cy="{{ $y($point[$key]) }}" r="4" fill="{{ $line['color'] }}" stroke="#111a33" stroke-width="2" />
                @endforeach
                <text x="{{ $x($count - 1) + 10 }}" y="{{ $y($last[$key]) + $labelOffset[$key] }}" class="fill-mist-300 text-[11px] font-medium">{{ $line['label'] }}</text>
            @endforeach

            {{-- Zonas de hover, más anchas que los puntos --}}
            @foreach ($points as $index => $point)
                <rect x="{{ $x($index) - $columnWidth / 2 }}" y="{{ $padding['top'] }}" width="{{ $columnWidth }}" height="{{ $plotHeight }}" fill="transparent"
                      data-point data-x="{{ $x($index) }}"
                      data-month="{{ Str::ucfirst($point['month']->locale('es')->isoFormat('MMMM YYYY')) }}"
                      data-income="{{ $currency->format($point['income']) }}"
                      data-investment="{{ $currency->format($point['investment']) }}"
                      data-profit="{{ ($point['profit'] < 0 ? '−' : '').$currency->format(abs($point['profit'])) }}"
                      data-loss="{{ $point['profit'] < 0 ? '1' : '0' }}" />
            @endforeach
        </svg>

        <div data-tooltip role="status" class="pointer-events-none absolute top-0 hidden min-w-44 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-xs shadow-lg">
            <p data-tooltip-month class="mb-1.5 font-semibold text-mist-100"></p>
            <p class="flex justify-between gap-4"><span class="flex items-center gap-1.5"><span class="inline-block h-0.5 w-3 rounded bg-[#0ea5c6]"></span>Ganado</span><span data-tooltip-income class="font-semibold text-mist-100 tabular-nums"></span></p>
            <p class="flex justify-between gap-4"><span class="flex items-center gap-1.5"><span class="inline-block h-0.5 w-3 rounded border-t-2 border-dashed border-[#f43f5e]"></span>Invertido</span><span data-tooltip-investment class="font-semibold text-mist-100 tabular-nums"></span></p>
            <p class="mt-1.5 flex justify-between gap-4 border-t border-ink-700 pt-1.5"><span data-tooltip-profit-label>Ganancia</span><span data-tooltip-profit class="font-semibold text-mist-100 tabular-nums"></span></p>
        </div>
    </div>
</div>
