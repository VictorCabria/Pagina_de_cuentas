{{-- Ganancia con signo: verde si es positiva, roja si es pérdida --}}
@props(['amount', 'currency'])

<span {{ $attributes->class(['text-emerald-300' => $amount >= 0, 'text-rose-300' => $amount < 0]) }}>{{ $amount < 0 ? '−' : '' }}{{ $currency->format(abs($amount)) }}</span>
