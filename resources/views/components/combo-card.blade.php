{{-- Tarjeta de un combo: banner, qué incluye, ahorro y botón para agregarlo al carrito --}}
@props(['combo', 'currency'])

@php
    $price = $combo->price($currency);
    $regular = $combo->regularPrice($currency);
    $savings = $combo->savings($currency);
    $purchasable = $combo->purchasable();
    $services = $combo->plans->pluck('service')->unique('id');
@endphp

<article @class([
    'group relative flex flex-col overflow-hidden rounded-3xl bg-shop-card ring-1 transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-cyan-500/10 motion-reduce:transition-none motion-reduce:hover:translate-y-0',
    'ring-pink-400/60' => $combo->is_featured && $purchasable,
    'ring-white/10 hover:ring-cyan-500/50' => ! $combo->is_featured && $purchasable,
    'ring-rose-500/30' => ! $purchasable,
])>
    {{-- Banner --}}
    <div class="relative aspect-[16/9] overflow-hidden">
        @if ($combo->imageUrl())
            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" loading="lazy"
                 class="size-full object-cover transition duration-500 group-hover:scale-105 {{ $purchasable ? '' : 'opacity-50 grayscale' }}">
        @else
            <div class="relative flex size-full flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-700 to-cyan-600 p-5 {{ $purchasable ? '' : 'opacity-50 grayscale' }}">
                <span class="pointer-events-none absolute -top-10 -right-10 size-40 rounded-full bg-cyan-300/25 blur-2xl" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -bottom-12 -left-6 size-40 rounded-full bg-pink-400/25 blur-2xl" aria-hidden="true"></span>
                <p class="relative max-w-[70%] font-display text-2xl leading-none font-extrabold tracking-tight text-white uppercase drop-shadow">{{ $combo->name }}</p>
                <div class="relative flex gap-2.5">
                    @foreach ($services->take(5) as $service)
                        <x-service-logo :service="$service" class="size-14 rounded-2xl text-xl shadow-lg shadow-black/30 ring-2 ring-white/20 transition duration-300 group-hover:-translate-y-1" style="transition-delay: {{ $loop->index * 40 }}ms" />
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $purchasable)
            <span class="absolute top-8 -right-12 z-10 w-48 rotate-45 bg-rose-500 py-1.5 text-center text-xs font-extrabold tracking-[0.2em] text-white uppercase shadow-lg shadow-rose-950/50">Agotado</span>
        @elseif ($combo->is_featured)
            <span class="absolute top-3 right-3 rounded-full bg-pink-400 px-3 py-1 text-xs font-bold text-pink-950 shadow-lg">⭐ Más popular</span>
        @endif
        @if ($purchasable && $savings > 0 && $regular > 0)
            <span class="absolute right-3 bottom-3 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white backdrop-blur">-{{ (int) round($savings / $regular * 100) }}%</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-6">
        <h3 class="flex items-center gap-2 font-display text-xl font-bold text-white">
            <svg class="size-5 shrink-0 text-cyan-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.375 3a1.875 1.875 0 0 0 0 3.75h1.875v4.5H3.375A1.875 1.875 0 0 1 1.5 9.375v-.75c0-1.036.84-1.875 1.875-1.875h3.193A3.375 3.375 0 0 1 12 2.753a3.375 3.375 0 0 1 5.432 3.997h3.943c1.035 0 1.875.84 1.875 1.875v.75c0 1.036-.84 1.875-1.875 1.875H12.75v-4.5h1.875a1.875 1.875 0 1 0-1.875-1.875V6.75h-1.5V4.875C11.25 3.839 10.41 3 9.375 3ZM11.25 12.75H3v6.75a2.25 2.25 0 0 0 2.25 2.25h6v-9ZM12.75 12.75v9h6.75a2.25 2.25 0 0 0 2.25-2.25v-6.75h-9Z"/></svg>
            {{ $combo->name }}
        </h3>
        @if ($combo->description)
            <p class="mt-1 text-sm text-slate-400">{{ $combo->description }}</p>
        @endif

        <p class="mt-5 text-xs font-semibold tracking-wider text-slate-500 uppercase">Planes incluidos</p>
        <ul class="mt-2 space-y-2">
            @foreach ($combo->plans as $plan)
                <li class="flex items-center gap-3 text-sm">
                    <x-service-logo :service="$plan->service" class="size-8 rounded-lg text-xs" />
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-white">{{ $plan->service->name }}</span>
                        <span class="block truncate text-xs text-slate-400">{{ $plan->name }} · {{ $plan->durationLabel() }}</span>
                    </span>
                    <svg class="size-5 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                </li>
            @endforeach
        </ul>

        <div class="mt-auto pt-6">
            <div class="flex items-end justify-between gap-3 border-t border-dashed border-white/10 pt-5">
                <div>
                    @if ($savings > 0)
                        <p class="text-xs text-slate-500">Por separado <span class="line-through">{{ $currency->format($regular) }}</span></p>
                    @endif
                    <p @class(['font-display text-3xl font-bold', 'text-cyan-400' => $purchasable, 'text-slate-500 line-through' => ! $purchasable])>
                        {{ $price->formatted() }}<span class="text-sm font-medium text-slate-500"> / {{ $combo->durationLabel() }}</span>
                    </p>
                </div>
                @if ($purchasable && $savings > 0)
                    <span class="shrink-0 rounded-full bg-pink-400/15 px-3 py-1 text-xs font-bold text-pink-300">Ahorras {{ $currency->format($savings) }}</span>
                @endif
            </div>

            @if ($purchasable)
                <form method="POST" action="{{ route('cart.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="combo_id" value="{{ $combo->id }}">
                    <button class="flex w-full items-center justify-center gap-2 rounded-full bg-cyan-400 px-6 py-3.5 font-semibold text-cyan-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Agregar al carrito
                    </button>
                </form>
            @else
                <p class="mt-4 rounded-full bg-rose-500/10 py-3 text-center text-sm font-semibold text-rose-300">Sin cupos por ahora</p>
            @endif
        </div>
    </div>
</article>
