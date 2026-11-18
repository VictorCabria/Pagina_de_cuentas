{{-- Tarjeta de un servicio en el catálogo: logo, precio "desde" y descuento --}}
@props(['service', 'currency'])

@php
    $price = $service->cheapestPrice($currency);
    $discount = $service->bestDiscount($currency);
    $inStock = $service->inStock();
@endphp

<a href="{{ route('store.service', $service) }}"
   data-service data-category="{{ $service->category?->slug }}"
   data-name="{{ Str::lower(Str::ascii($service->name.' '.$service->tagline)) }}"
   {{ $attributes->class(['group relative flex flex-col overflow-hidden rounded-2xl bg-shop-card p-5 ring-1 ring-white/10 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-cyan-500/10 hover:ring-cyan-500/50 motion-reduce:transition-none motion-reduce:hover:translate-y-0', 'ring-rose-500/30!' => ! $inStock]) }}>
    @if (! $inStock)
        {{-- Cinta diagonal: que se note de lejos que no hay cupos --}}
        <span class="absolute top-8 -right-12 z-10 w-48 rotate-45 bg-rose-500 py-1.5 text-center text-xs font-extrabold tracking-[0.2em] text-white uppercase shadow-lg shadow-rose-950/50">Agotado</span>
    @elseif ($discount > 0)
        <span class="absolute top-4 right-4 rounded-full bg-pink-400 px-2.5 py-1 text-xs font-bold text-pink-950">-{{ $discount }}%</span>
    @elseif ($service->is_featured)
        <span class="absolute top-4 right-4 rounded-full bg-cyan-500/10 px-2.5 py-1 text-xs font-semibold text-cyan-400">Popular</span>
    @endif

    <x-service-logo :service="$service" class="size-14 text-2xl {{ $inStock ? '' : 'opacity-40 grayscale' }}" />

    <h3 class="mt-4 font-display text-lg font-bold text-white">{{ $service->name }}</h3>
    <p class="mt-1 line-clamp-2 min-h-10 text-sm text-slate-400">{{ $service->tagline }}</p>

    <div class="mt-5 flex items-end justify-between gap-3 border-t border-dashed border-white/10 pt-4">
        @if ($price)
            <div>
                <p class="text-xs text-slate-500">Desde</p>
                <p @class(['font-display text-xl font-bold', 'text-cyan-400' => $inStock, 'text-slate-500 line-through decoration-rose-500/70' => ! $inStock])>
                    {{ $price->isFree() ? 'Gratis' : $currency->format($price->perMonth()) }}<span class="text-sm font-medium text-slate-500">/mes</span>
                </p>
            </div>
        @else
            <p class="text-sm font-medium text-slate-500">Sin cupos por ahora</p>
        @endif
        @if (! $inStock)
            <span class="shrink-0 rounded-full bg-rose-500/15 px-3 py-1.5 text-xs font-bold text-rose-400">Sin cupos</span>
        @else
        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-cyan-500/10 text-cyan-400 transition group-hover:bg-cyan-600 group-hover:text-white" aria-hidden="true">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
        </span>
        @endif
    </div>
</a>
