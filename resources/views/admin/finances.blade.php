<x-layouts.admin title="Finanzas">
    <x-slot:actions>
        <x-currency-switch :current="$currency" />
    </x-slot:actions>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <p class="text-xs uppercase tracking-wider">Invertido</p>
            <p class="mt-2 text-2xl font-bold text-mist-100">{{ $currency->format($totals['investment']) }}</p>
            <p class="form-hint">{{ $totals['purchases'] }} {{ $totals['purchases'] === 1 ? 'compra' : 'compras' }}</p>
        </div>
        <div class="card">
            <p class="text-xs uppercase tracking-wider">Ganado (ventas)</p>
            <p class="mt-2 text-2xl font-bold text-mist-100">{{ $currency->format($totals['income']) }}</p>
            <p class="form-hint">{{ $totals['orders'] }} {{ $totals['orders'] === 1 ? 'orden pagada' : 'órdenes pagadas' }}</p>
        </div>
        <div class="card">
            <p class="text-xs uppercase tracking-wider">Ganancia</p>
            <p class="mt-2 text-2xl font-bold"><x-profit :amount="$totals['profit']" :currency="$currency" /></p>
            <p class="form-hint">Ganado menos invertido</p>
        </div>
    </div>

    <x-finance-chart :points="$chart" :currency="$currency" class="mt-6" />

    {{-- Por mes --}}
    <h2 class="mt-10 mb-3 text-sm font-semibold uppercase tracking-wider text-mist-300">Por mes</h2>
    @if ($monthly->isEmpty())
        <div class="card text-center text-sm">Todavía no hay órdenes pagadas ni compras anotadas.</div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Mes</th>
                        <th class="px-4 py-3 text-right">Órdenes</th>
                        <th class="px-4 py-3 text-right">Ganado</th>
                        <th class="px-4 py-3 text-right">Compras</th>
                        <th class="px-4 py-3 text-right">Invertido</th>
                        <th class="px-4 py-3 text-right">Ganancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($monthly as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-mist-100">{{ Str::ucfirst($row['month']->locale('es')->isoFormat('MMMM YYYY')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($row['income']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['purchases'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($row['investment']) }}</td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap"><x-profit :amount="$row['profit']" :currency="$currency" /></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-ink-900 font-semibold">
                    <tr>
                        <td class="px-4 py-3 text-mist-100">Total</td>
                        <td class="px-4 py-3 text-right">{{ $totals['orders'] }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($totals['income']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $totals['purchases'] }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($totals['investment']) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap"><x-profit :amount="$totals['profit']" :currency="$currency" /></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Por plataforma --}}
    @if ($products->isNotEmpty())
        <h2 class="mt-10 mb-3 text-sm font-semibold uppercase tracking-wider text-mist-300">Por plataforma</h2>
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Plataforma</th>
                        <th class="px-4 py-3 text-right">Vendidas</th>
                        <th class="px-4 py-3 text-right">Ganado</th>
                        <th class="px-4 py-3 text-right">Compradas</th>
                        <th class="px-4 py-3 text-right">Invertido</th>
                        <th class="px-4 py-3 text-right">Ganancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($products as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-mist-100">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['sold'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($row['income']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['bought'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($row['investment']) }}</td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap"><x-profit :amount="$row['profit']" :currency="$currency" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Detalle --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-mist-300">Órdenes pagadas</h2>
            @if ($orders->isEmpty())
                <div class="card text-sm">Cuando marques una orden como <strong class="text-mist-100">Pagada</strong>, aparece aquí.</div>
            @else
                <div class="overflow-x-auto rounded-xl border border-ink-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Orden</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-accent hover:underline">{{ $order->number() }}</a>
                                        <p class="text-xs">{{ $order->paid_at->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-mist-100">{{ $order->customer_name }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <span class="font-semibold text-mist-100">{{ $currency->format($finances->income($order, $currency)) }}</span>
                                        @if ($order->currency !== $currency)
                                            <p class="text-xs">{{ $order->formattedTotal() }} {{ $order->currency->value }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-mist-300">Compras (inversión)</h2>
                <a href="{{ route('admin.purchases.create') }}" class="btn-ghost px-2.5 py-1 text-xs">+ Anotar compra</a>
            </div>
            @if ($purchases->isEmpty())
                <div class="card text-sm">Cuando anotes una compra en <a href="{{ route('admin.purchases.index') }}" class="text-accent hover:underline">Inversión</a>, aparece aquí.</div>
            @else
                <div class="overflow-x-auto rounded-xl border border-ink-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Compra</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                            @foreach ($purchases as $purchase)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.purchases.edit', $purchase) }}" class="font-medium text-mist-100 hover:text-accent">{{ $purchase->label() }}</a>
                                        <p class="text-xs">{{ $purchase->purchased_on->format('d/m/Y') }}{{ $purchase->service && $purchase->description ? ' · '.$purchase->description : '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <span class="font-semibold text-mist-100">{{ $currency->format($finances->cost($purchase, $currency)) }}</span>
                                        @if ($purchase->currency !== $currency)
                                            <p class="text-xs">{{ $purchase->formattedAmount() }} {{ $purchase->currency->value }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <p class="form-hint mt-6">
        Ganado = órdenes Pagadas o Entregadas, en el mes en que se cobraron. Invertido = las compras anotadas en Inversión, en el mes de la compra.
        Los montos en otra moneda se convierten con la tasa actual.
    </p>
</x-layouts.admin>
