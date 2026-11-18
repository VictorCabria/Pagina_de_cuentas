@props(['title' => null, 'currency', 'description' => null])

@php
    $storeName = \App\Models\Setting::get('store_name');
    $whatsapp = \App\Models\Setting::get('whatsapp');
    $whatsappDisplay = \App\Support\Phone::format($whatsapp);
    $rate = app(\App\Support\ExchangeRate::class)->current();
    $cartCount = app(\App\Support\Cart::class)->count();
@endphp

<!DOCTYPE html>
<html lang="es" class="scheme-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$storeName : $storeName.' · Suscripciones premium al mejor precio' }}</title>
    <meta name="description" content="{{ $description ?? 'Netflix, Spotify, ChatGPT y más plataformas premium a precios bajos, con garantía y entrega rápida.' }}">
    <meta name="theme-color" content="#0a0f1f">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-shop-bg font-sans text-slate-300 antialiased">
    {{-- Franja superior con la tasa del día --}}
    <div class="bg-shop-deep text-xs text-cyan-100/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6">
            <p>
                <span class="mr-1.5 inline-block size-1.5 rounded-full bg-pink-400 align-middle"></span>
                Dólar hoy: <strong class="font-semibold text-white">1 USD = {{ \App\Enums\Currency::COP->format($rate) }} COP</strong>
                <span class="hidden sm:inline">· tasa de mercado actualizada cada hora</span>
            </p>
            @if ($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="hidden font-medium hover:text-white sm:block">Soporte por WhatsApp</a>
            @endif
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-shop-bg/85 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <x-brand-mark class="size-10" />
                <span class="hidden font-display text-lg font-bold tracking-tight text-white sm:block">{{ $storeName }}</span>
            </a>

            <form action="{{ route('home') }}#catalogo" class="relative min-w-0 flex-1 md:max-w-md" role="search">
                <svg class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" data-search placeholder="Buscar Netflix, Spotify, ChatGPT…" aria-label="Buscar servicio"
                       class="w-full rounded-full border-0 bg-white/5 ring-1 ring-white/10 py-2.5 pr-4 pl-10 text-sm text-white placeholder:text-slate-500 focus:bg-white/10 focus:ring-2 focus:ring-cyan-500/40 focus:outline-none">
            </form>

            <nav class="ml-auto hidden items-center gap-6 text-sm font-medium text-slate-300 lg:flex">
                <a href="{{ route('home') }}#combos" class="hover:text-cyan-300">Combos</a>
                <a href="{{ route('home') }}#licencias" class="hover:text-cyan-300">Licencias</a>
                <a href="{{ route('home') }}#catalogo" class="hover:text-cyan-300">Catálogo</a>
                <a href="{{ route('home') }}#como-funciona" class="hover:text-cyan-300">Cómo funciona</a>
                <a href="{{ route('home') }}#preguntas" class="hover:text-cyan-300">Preguntas</a>
            </nav>

            <div class="flex shrink-0 rounded-full bg-white/5 p-1 text-xs font-semibold" aria-label="Moneda">
                @foreach (\App\Enums\Currency::cases() as $option)
                    <a href="{{ route('store.currency', $option) }}"
                       @class([
                           'rounded-full px-3 py-1.5 transition',
                           'bg-cyan-400 text-cyan-950 shadow-sm' => $option === $currency,
                           'text-slate-400 hover:text-white' => $option !== $currency,
                       ])
                       @if ($option === $currency) aria-current="true" @endif>{{ $option->value }}</a>
                @endforeach
            </div>

            <a href="{{ route('cart.show') }}" class="relative grid size-10 shrink-0 place-items-center rounded-full bg-white/5 text-slate-200 ring-1 ring-white/10 transition hover:text-cyan-300 hover:ring-cyan-500/50"
               aria-label="Carrito ({{ $cartCount }})">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span data-cart-count @class(['absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full bg-pink-400 px-1 text-[11px] font-bold text-pink-950 ring-2 ring-shop-bg', 'hidden' => $cartCount === 0])>{{ $cartCount }}</span>
            </a>
        </div>
    </header>

    {{-- Avisos del carrito --}}
    @if (session('cart_added') || session('cart_error'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6" role="status">
            @if (session('cart_added'))
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-cyan-400/10 px-5 py-3 text-sm text-cyan-100 ring-1 ring-cyan-400/40">
                    <p>✅ {{ session('cart_added') }}</p>
                    <a href="{{ route('cart.show') }}" class="rounded-full bg-cyan-400 px-4 py-1.5 text-xs font-bold text-cyan-950 transition hover:bg-cyan-300">Ver carrito y pedir</a>
                </div>
            @else
                <div class="rounded-2xl bg-rose-500/10 px-5 py-3 text-sm text-rose-200 ring-1 ring-rose-500/40">⚠️ {{ session('cart_error') }}</div>
            @endif
        </div>
    @endif

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-white/10 bg-shop-deep text-cyan-100/70">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3">
            <div>
                <p class="flex items-center gap-2.5 font-display text-xl font-bold text-white"><x-brand-mark class="size-9" /> {{ $storeName }}</p>
                <p class="mt-3 max-w-xs text-sm">Plataformas de streaming, música, IA y productividad a precios accesibles, con garantía durante todo el periodo.</p>
            </div>
            <div class="text-sm">
                <p class="font-semibold text-white">Tienda</p>
                <ul class="mt-3 space-y-2">
                    <li><a href="{{ route('home') }}#catalogo" class="hover:text-white">Catálogo</a></li>
                    <li><a href="{{ route('home') }}#licencias" class="hover:text-white">Licencias</a></li>
                    <li><a href="{{ route('home') }}#como-funciona" class="hover:text-white">Cómo funciona</a></li>
                    <li><a href="{{ route('home') }}#preguntas" class="hover:text-white">Preguntas frecuentes</a></li>
                </ul>
            </div>
            <div class="text-sm">
                <p class="font-semibold text-white">¿Necesitas ayuda?</p>
                @if ($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"
                       class="mt-3 inline-flex items-center gap-2 rounded-full bg-cyan-400 px-5 py-2.5 font-semibold text-cyan-950 transition hover:bg-cyan-300">
                        Escríbenos por WhatsApp
                    </a>
                @else
                    <p class="mt-3">Muy pronto tendremos atención por WhatsApp.</p>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10 py-5 text-center text-xs">© {{ date('Y') }} {{ $storeName }}. Todos los derechos reservados.</div>
    </footer>
    @if ($whatsapp)
        <x-whatsapp-widget :number="$whatsapp" :display="$whatsappDisplay" :store-name="$storeName" />
    @endif
</body>
</html>
