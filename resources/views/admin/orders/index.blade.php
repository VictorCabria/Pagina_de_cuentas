<x-layouts.admin title="Órdenes">
    {{-- Filtro por estado --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.orders.index') }}"
           @class(['rounded-full px-3.5 py-1.5 text-sm font-medium transition', 'bg-accent text-cyan-950' => ! $status, 'bg-ink-800 text-mist-300 hover:text-mist-100' => $status])>
            Todas <span class="opacity-70">{{ $counts->sum() }}</span>
        </a>
        @foreach (\App\Enums\OrderStatus::cases() as $option)
            <a href="{{ route('admin.orders.index', ['estado' => $option->value]) }}"
               @class(['rounded-full px-3.5 py-1.5 text-sm font-medium transition', 'bg-accent text-cyan-950' => $status === $option, 'bg-ink-800 text-mist-300 hover:text-mist-100' => $status !== $option])>
                {{ $option->label() }} <span class="opacity-70">{{ $counts[$option->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    @if ($orders->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">{{ $status ? 'No hay órdenes '.Str::lower($status->label()).'s.' : 'Todavía no hay órdenes.' }}</p>
            <p class="mt-1 text-sm">Cuando un cliente envíe su carrito por WhatsApp, la orden aparecerá aquí.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Orden</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="hidden px-4 py-3 md:table-cell">Productos</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-semibold text-accent hover:underline">{{ $order->number() }}</a>
                                <p class="text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-mist-100">{{ $order->customer_name }}</p>
                                <a href="https://wa.me/{{ $order->customer_phone }}" target="_blank" rel="noopener" class="text-xs hover:text-accent">{{ $order->formattedPhone() }}</a>
                            </td>
                            <td class="hidden max-w-xs px-4 py-3 text-xs md:table-cell">
                                {{ $order->items->map(fn ($item) => $item->service_name.($item->quantity > 1 ? ' ×'.$item->quantity : ''))->join(', ') }}
                            </td>
                            <td class="px-4 py-3 font-semibold whitespace-nowrap text-mist-100">{{ $order->formattedTotal() }} <span class="text-xs font-normal text-mist-400">{{ $order->currency->value }}</span></td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="notes" value="{{ $order->notes }}">
                                    <select name="status" onchange="this.form.submit()" aria-label="Estado de la orden {{ $order->number() }}"
                                            class="form-input w-auto py-1 pr-8 text-xs">
                                        @foreach (\App\Enums\OrderStatus::cases() as $option)
                                            <option value="{{ $option->value }}" @selected($order->status === $option)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-ghost px-2.5 py-1 text-xs">Ver</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
        <p class="form-hint mt-3">Cambia el estado desde la lista: Pendiente → Pagada → Entregada (o Cancelada).</p>
    @endif
</x-layouts.admin>
