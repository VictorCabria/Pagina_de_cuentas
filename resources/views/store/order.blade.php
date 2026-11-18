<x-layouts.store :currency="$currency" :title="'Pedido '.$order->number()">
    <div class="mx-auto max-w-2xl px-4 pt-10 sm:px-6">
        <div class="rounded-3xl bg-shop-card p-7 text-center ring-1 ring-white/10 sm:p-10" data-order-sent data-whatsapp-url="{{ $whatsappUrl }}">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-[#25d366]/15 text-[#25d366]">
                <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <p class="mt-5 text-sm font-semibold text-cyan-400">Pedido {{ $order->number() }}</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-white">¡Ya casi! Envíanos tu pedido</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-400">
                Abrimos WhatsApp con tu pedido ya escrito. Solo dale <strong class="text-white">enviar</strong> y te respondemos con los datos de pago.
            </p>

            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
               class="mt-7 inline-flex items-center justify-center gap-2 rounded-full bg-[#25d366] px-8 py-4 font-bold text-white shadow-lg shadow-[#25d366]/25 transition hover:-translate-y-0.5 hover:bg-[#1ebe5b]">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8 0-1.4.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.6-.1 1Z"/></svg>
                Enviar pedido por WhatsApp
            </a>
            <p class="mt-3 text-xs text-slate-500">¿No se abrió WhatsApp? Toca el botón de arriba.</p>
        </div>

        <div class="mt-6 rounded-3xl bg-shop-card p-6 ring-1 ring-white/10 sm:p-7">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-white">Detalle del pedido</h2>
                <span class="text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <ul class="mt-4 divide-y divide-white/10">
                @foreach ($order->items as $item)
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span>
                            <span class="block font-medium text-white">{{ $item->service_name }}{{ $item->quantity > 1 ? ' ×'.$item->quantity : '' }}</span>
                            <span class="text-slate-400">{{ $item->plan_name }} · {{ $item->durationLabel() }}</span>
                        </span>
                        <span class="text-right font-semibold text-white">
                            {{ $order->currency->format($item->subtotal()) }}
                            @if ($item->discount > 0)
                                <span class="block text-[11px] font-bold text-pink-300">-{{ $item->discount }}%</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-2 flex items-baseline justify-between border-t border-white/10 pt-4">
                <span>Total</span>
                <span class="font-display text-2xl font-bold text-cyan-400">{{ $order->formattedTotal() }} <span class="text-sm font-medium text-slate-400">{{ $order->currency->value }}</span></span>
            </div>
            <p class="mt-4 text-xs text-slate-500">A nombre de {{ $order->customer_name }} · {{ $order->formattedPhone() }}</p>
        </div>

        <p class="mt-6 text-center"><a href="{{ route('home') }}" class="text-sm font-medium text-cyan-400 hover:text-cyan-300">← Volver a la tienda</a></p>
    </div>
</x-layouts.store>
