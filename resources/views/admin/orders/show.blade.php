<x-layouts.admin :title="'Orden '.$order->number()">
    <x-slot:actions>
        <a href="{{ route('admin.orders.index') }}" class="btn-ghost">← Todas las órdenes</a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs uppercase tracking-wider">Productos</p>
                <x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge>
            </div>
            <ul class="mt-4 divide-y divide-ink-700">
                @foreach ($order->items as $item)
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-mist-100">{{ $item->service_name }}{{ $item->quantity > 1 ? ' ×'.$item->quantity : '' }}</p>
                            <p class="text-xs">{{ $item->plan_name }} · {{ $item->durationLabel() }}
                                · {{ $order->currency->format((float) $item->unit_price) }} c/u
                                @if ($item->discount > 0)
                                    <x-badge color="pink" class="ml-1">-{{ $item->discount }}%</x-badge>
                                @endif
                            </p>
                        </div>
                        <p class="font-semibold text-mist-100">{{ $order->currency->format($item->subtotal()) }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-2 flex items-baseline justify-between border-t border-ink-700 pt-4">
                <span>Total</span>
                <span class="text-2xl font-bold text-mist-100">{{ $order->formattedTotal() }} <span class="text-sm font-normal text-mist-400">{{ $order->currency->value }}</span></span>
            </div>
            <p class="form-hint mt-4">Creada el {{ $order->created_at->format('d/m/Y \a \l\a\s H:i') }}. Los precios quedan guardados tal como se vieron al pedir.</p>

            <div class="mt-6 border-t border-ink-700 pt-5">
                <p class="text-xs uppercase tracking-wider">Cuentas entregadas</p>
                @if ($order->subscriptions->isEmpty())
                    <p class="mt-2 text-sm">Al marcar la orden como <strong class="text-mist-100">Pagada</strong> se crean aquí las cuentas, listas para ponerles el correo.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($order->subscriptions as $subscription)
                            @php($status = $subscription->status())
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2">
                                    <x-service-logo :service="$subscription->service" class="size-6 rounded-md text-[10px]" />
                                    <span class="text-mist-100">{{ $subscription->service->name }}</span>
                                    <span class="text-xs">{{ $subscription->account_email ?? 'sin correo' }}</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <x-badge :color="$status['color']">{{ $status['label'] }}</x-badge>
                                    <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn-ghost px-2 py-0.5 text-xs">{{ $subscription->account_email ? 'Editar' : 'Poner correo' }}</a>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <p class="text-xs uppercase tracking-wider">Cliente</p>
                <p class="mt-2 text-lg font-semibold text-mist-100">{{ $order->customer_name }}</p>
                <p class="text-sm">{{ $order->formattedPhone() }}</p>
                <a href="https://wa.me/{{ $order->customer_phone }}?text={{ rawurlencode('Hola '.$order->customer_name.', te escribimos por tu pedido '.$order->number().'.') }}"
                   target="_blank" rel="noopener"
                   class="btn mt-4 w-full bg-[#25d366] text-white hover:bg-[#1ebe5b]">Escribir al cliente por WhatsApp</a>
            </div>

            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="card space-y-4">
                @csrf
                @method('PATCH')
                <x-select name="status" label="Estado" :value="$order->status->value"
                          :options="collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
                <div>
                    <label for="notes" class="form-label">Notas internas</label>
                    <textarea id="notes" name="notes" rows="4" maxlength="2000" class="form-input"
                              placeholder="Ej. pagó por Nequi, cuenta entregada el lunes…">{{ old('notes', $order->notes) }}</textarea>
                    <p class="form-hint">Solo se ven en el panel.</p>
                </div>
                <button class="btn-primary w-full">Guardar</button>
            </form>

            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                  onsubmit="return confirm('¿Eliminar la orden {{ $order->number() }}? No se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button class="btn-danger w-full">Eliminar orden</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
