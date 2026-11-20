<x-layouts.admin title="Inversión">
    <x-slot:actions>
        <x-currency-switch :current="$currency" />
        <a href="{{ route('admin.purchases.create') }}" class="btn-primary">+ Anotar compra</a>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <p class="text-xs uppercase tracking-wider">Total invertido</p>
            <p class="mt-2 text-3xl font-bold text-mist-100">{{ $currency->format($total) }}</p>
            <p class="form-hint">{{ $purchases->count() }} {{ $purchases->count() === 1 ? 'compra' : 'compras' }} en total</p>
        </div>
        <div class="card">
            <p class="text-xs uppercase tracking-wider">Este mes</p>
            <p class="mt-2 text-3xl font-bold text-mist-100">{{ $currency->format($thisMonth) }}</p>
            <p class="form-hint">{{ Str::ucfirst(now()->locale('es')->isoFormat('MMMM YYYY')) }}</p>
        </div>
        <div class="card flex flex-col justify-center">
            <p class="text-sm">¿Cuánto ganaste con esto?</p>
            <a href="{{ route('admin.finances', ['moneda' => $currency->value]) }}" class="mt-1 font-semibold text-accent hover:underline">Ver Finanzas →</a>
        </div>
    </div>

    <div class="mt-8">
        @if ($purchases->isEmpty())
            <div class="card text-center">
                <p class="text-mist-100">Todavía no has anotado compras.</p>
                <p class="mt-1 text-sm">Cada vez que compres una cuenta (Netflix, Disney+…), anota cuánto gastaste y se suma a lo invertido.</p>
                <a href="{{ route('admin.purchases.create') }}" class="btn-primary mt-4 inline-block">+ Anotar la primera compra</a>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-ink-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Plataforma</th>
                            <th class="hidden px-4 py-3 md:table-cell">Detalle</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                        @foreach ($purchases as $purchase)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $purchase->purchased_on->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-medium text-mist-100">{{ $purchase->label() }}</td>
                                <td class="hidden max-w-xs px-4 py-3 text-xs md:table-cell">
                                    {{ $purchase->service ? $purchase->description : '' }}
                                    @if ($purchase->notes)
                                        <span class="block text-mist-400">{{ Str::limit($purchase->notes, 80) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="font-semibold text-mist-100">{{ $currency->format($finances->cost($purchase, $currency)) }}</span>
                                    @if ($purchase->currency !== $currency)
                                        <span class="block text-xs">{{ $purchase->formattedAmount() }} {{ $purchase->currency->value }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                        <form method="POST" action="{{ route('admin.purchases.destroy', $purchase) }}"
                                              onsubmit="return confirm('¿Eliminar la compra de {{ e($purchase->label()) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-ghost px-2.5 py-1 text-xs text-rose-300">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-ink-900 font-semibold">
                        <tr>
                            <td class="px-4 py-3 text-mist-100" colspan="2">Total</td>
                            <td class="hidden md:table-cell"></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-mist-100">{{ $currency->format($total) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
