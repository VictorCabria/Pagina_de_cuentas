{{-- Etiqueta de estado: green | red | amber | pink (descuentos) | blue | gray --}}
@props(['color' => 'gray'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
    'bg-emerald-500/10 text-emerald-300' => $color === 'green',
    'bg-rose-500/10 text-rose-300' => $color === 'red',
    'bg-amber-500/10 text-amber-300' => $color === 'amber',
    'bg-pink-500/15 text-pink-300' => $color === 'pink',
    'bg-accent-soft text-accent' => $color === 'blue',
    'bg-ink-700 text-mist-300' => $color === 'gray',
]) }}>{{ $slot }}</span>
