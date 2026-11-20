@props(['title' => 'Panel'])

@php
    $nav = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Inicio'],
        ['route' => 'admin.finances', 'match' => 'admin.finances', 'label' => 'Finanzas'],
        ['route' => 'admin.purchases.index', 'match' => 'admin.purchases.*', 'label' => 'Inversión'],
        ['route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'label' => 'Órdenes', 'badge' => \App\Models\Order::where('status', 'pending')->count()],
        ['route' => 'admin.subscriptions.index', 'match' => 'admin.subscriptions.*', 'label' => 'Cuentas', 'badge' => \App\Models\Subscription::missingAccount()->count()],
        ['route' => 'admin.services.index', 'match' => ['admin.services.*', 'admin.plans.*'], 'label' => 'Servicios'],
        ['route' => 'admin.combos.index', 'match' => 'admin.combos.*', 'label' => 'Combos'],
        ['route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'label' => 'Categorías'],
        ['route' => 'admin.promotions.index', 'match' => 'admin.promotions.*', 'label' => 'Promociones'],
        ['route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'label' => 'Notificaciones', 'badge' => auth()->user()->unreadNotifications()->count()],
        ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'label' => 'Monedas y ajustes'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="bg-ink-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Panel de {{ \App\Models\Setting::get('store_name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-950 font-sans text-mist-400 antialiased">
    <div class="lg:flex">
        <aside class="border-b border-ink-700 bg-ink-900 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:border-r lg:border-b-0">
            <div class="flex items-center justify-between px-6 py-5 lg:block">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 font-display text-base font-bold text-mist-100">
                    <x-brand-mark class="size-9" />
                    {{ \App\Models\Setting::get('store_name') }}
                </a>
                <a href="{{ route('home') }}" target="_blank" class="text-xs text-mist-400 hover:text-accent lg:mt-2 lg:block">Ver tienda ↗</a>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-3 pb-3 lg:flex-col lg:pb-0">
                @foreach ($nav as $item)
                    @php($active = request()->routeIs(...(array) $item['match']))
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition',
                           'bg-accent-soft text-accent' => $active,
                           'text-mist-300 hover:bg-ink-800 hover:text-mist-100' => ! $active,
                       ])>
                        {{ $item['label'] }}
                        @if (! empty($item['badge']))
                            <span class="ml-1.5 rounded-full bg-pink-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="hidden px-3 lg:absolute lg:bottom-6 lg:block lg:w-full">
                @csrf
                <button class="w-full rounded-md px-3 py-2 text-left text-sm text-mist-400 hover:bg-ink-800 hover:text-mist-100">
                    Cerrar sesión
                </button>
            </form>
        </aside>

        <main class="flex-1 px-6 py-8 lg:px-12 lg:py-12">
            <div class="mx-auto max-w-5xl">
                <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold tracking-tight text-mist-100">{{ $title }}</h1>
                    <div class="ml-auto flex items-center gap-3">
                        {{ $actions ?? '' }}
                        <x-admin-bell />
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-md border border-accent/30 bg-accent-soft px-4 py-3 text-sm text-accent" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" role="alert">
                        Revisa los campos marcados: hay {{ $errors->count() }} {{ Str::plural('error', $errors->count()) }}.
                    </div>
                @endif

                {{ $slot }}

                <form method="POST" action="{{ route('logout') }}" class="mt-12 lg:hidden">
                    @csrf
                    <button class="btn-ghost">Cerrar sesión</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
