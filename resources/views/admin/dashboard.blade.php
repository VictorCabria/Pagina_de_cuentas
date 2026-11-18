<x-layouts.admin title="Inicio">
    <x-slot:actions>
        <a href="{{ route('admin.services.create') }}" class="btn-primary">+ Nuevo servicio</a>
    </x-slot:actions>

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
