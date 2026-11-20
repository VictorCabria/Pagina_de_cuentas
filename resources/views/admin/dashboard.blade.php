<x-layouts.admin title="Inicio">
    <x-slot:actions>
        <a href="{{ route('admin.services.create') }}" class="btn-primary">+ Nuevo servicio</a>
    </x-slot:actions>

    <section class="mb-8" aria-labelledby="finances-title">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 id="finances-title" class="text-sm font-semibold uppercase tracking-wider text-mist-300">Mis cuentas</h2>
            <div class="flex items-center gap-3">
                <x-currency-switch :current="$reportCurrency" />
                <a href="{{ route('admin.purchases.create') }}" class="btn-ghost px-2.5 py-1 text-xs">+ Anotar compra</a>
                <a href="{{ route('admin.finances', ['moneda' => $reportCurrency->value]) }}" class="text-sm text-accent hover:underline">Ver cuadro completo →</a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card">
                <p class="text-xs uppercase tracking-wider">Invertido</p>
                <p class="mt-2 text-2xl font-bold text-mist-100">{{ $reportCurrency->format($totals['investment']) }}</p>
                <p class="form-hint">{{ $totals['purchases'] }} {{ $totals['purchases'] === 1 ? 'compra' : 'compras' }}</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase tracking-wider">Ganado (ventas)</p>
                <p class="mt-2 text-2xl font-bold text-mist-100">{{ $reportCurrency->format($totals['income']) }}</p>
                <p class="form-hint">{{ $totals['orders'] }} {{ $totals['orders'] === 1 ? 'orden pagada' : 'órdenes pagadas' }}</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase tracking-wider">Ganancia</p>
                <p class="mt-2 text-2xl font-bold"><x-profit :amount="$totals['profit']" :currency="$reportCurrency" /></p>
                <p class="form-hint">Ganado menos invertido</p>
            </div>
            <div class="card">
                <p class="text-xs uppercase tracking-wider">Este mes</p>
                <p class="mt-2 text-2xl font-bold"><x-profit :amount="$thisMonth['profit']" :currency="$reportCurrency" /></p>
                <p class="form-hint">{{ $reportCurrency->format($thisMonth['income']) }} ganado · {{ $reportCurrency->format($thisMonth['investment']) }} invertido</p>
            </div>
        </div>

        <x-finance-chart :points="$chart" :currency="$reportCurrency" class="mt-4" />
    </section>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as $label => $value)
            <div class="card">
                <p class="text-xs uppercase tracking-wider">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-mist-100">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider">Tasa de cambio</p>
                    <p class="mt-2 text-2xl font-bold text-mist-100">1 USD = {{ \App\Enums\Currency::COP->format($rate) }} COP</p>
                    <p class="form-hint">
                        @if ($exchangeRate->isAutomatic())
                            Automática (mercado en vivo){{ $exchangeRate->updatedAt() ? ' · actualizada '.$exchangeRate->updatedAt()->locale('es')->diffForHumans() : '' }}
                        @else
                            Manual
                        @endif
                    </p>
                    <p class="form-hint">Moneda por defecto de la tienda: <strong class="text-mist-100">{{ $currency->label() }}</strong></p>
                </div>
                <a href="{{ route('admin.settings.edit') }}" class="btn-ghost px-2.5 py-1 text-xs">Cambiar</a>
            </div>
        </div>

        <div class="card">
            <p class="text-xs uppercase tracking-wider">Agotados ahora</p>
            @if ($soldOut->isEmpty() && $unavailablePlans->isEmpty())
                <p class="mt-2 text-sm text-emerald-300">Todo está disponible.</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($soldOut as $service)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-mist-100 hover:text-accent">{{ $service->name }}</a>
                            <x-stock-toggle :action="route('admin.services.toggle', $service)" :available="false" />
                        </li>
                    @endforeach
                    @foreach ($unavailablePlans as $plan)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.services.edit', $plan->service) }}" class="hover:text-accent">
                                <span class="text-mist-100">{{ $plan->service->name }}</span> · {{ $plan->name }}
                            </a>
                            <x-stock-toggle :action="route('admin.plans.toggle', $plan)" :available="false" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.admin>
