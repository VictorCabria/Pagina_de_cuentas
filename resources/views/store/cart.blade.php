<x-layouts.store :currency="$currency" title="Tu carrito">
    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
        <h1 class="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Tu carrito</h1>
        <p class="mt-1 text-sm text-slate-400">Revisa tu pedido y envíalo por WhatsApp. El pago lo coordinamos por el chat.</p>

        @if ($lines->isEmpty())
            <div class="mt-8 rounded-3xl bg-shop-card p-12 text-center ring-1 ring-white/10">
                <p class="text-5xl" aria-hidden="true">🛒</p>
                <p class="mt-4 font-display text-xl font-bold text-white">Tu carrito está vacío</p>
                <p class="mt-1 text-sm text-slate-400">Elige una plataforma y agrega el plan que quieras.</p>
                <a href="{{ route('home') }}#catalogo" class="mt-6 inline-flex rounded-full bg-cyan-400 px-7 py-3 text-sm font-bold text-cyan-950 transition hover:bg-cyan-300">Ver catálogo</a>
            </div>
        @else
            <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.5fr_1fr]">
                {{-- Productos --}}
                <ul class="space-y-3" data-cart-lines>
                    @foreach ($lines as $line)
                        @php($purchasable = $line->isPurchasable())
                        <li data-cart-line @class(['flex flex-wrap items-center gap-4 rounded-2xl bg-shop-card p-4 ring-1 transition duration-300 sm:flex-nowrap sm:p-5', 'ring-white/10' => $purchasable, 'ring-rose-500/40' => ! $purchasable])>
                            <a href="{{ $line->item->storeUrl() }}" class="shrink-0">
                                <x-sellable-logo :item="$line->item" class="size-14 text-xl {{ $purchasable ? '' : 'opacity-40 grayscale' }}" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $line->item->storeUrl() }}" class="font-semibold text-white hover:text-cyan-300">{{ $line->item->cartTitle() }}</a>
                                <p class="text-sm text-slate-400">{{ $line->item->cartSubtitle() }}</p>
                                @if (! $purchasable)
                                    <span class="mt-1 inline-block rounded-full bg-rose-500/15 px-2.5 py-0.5 text-xs font-bold text-rose-400 uppercase">Agotado · no se incluirá</span>
                                @elseif ($line->price->hasDiscount())
                                    <span class="mt-1 inline-block rounded-full bg-pink-400 px-2 py-0.5 text-[11px] font-bold text-pink-950">-{{ $line->price->discount }}%</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-4 sm:gap-6">
                                {{-- Cantidad --}}
                                <div class="flex items-center rounded-full bg-white/5 ring-1 ring-white/10">
                                    <form method="POST" action="{{ route('cart.update', $line->item->cartKey()) }}" data-cart-step="-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $line->quantity - 1 }}">
                                        <button class="grid size-9 place-items-center rounded-full text-lg text-slate-300 hover:text-white" aria-label="Quitar uno">−</button>
                                    </form>
                                    <span class="w-6 text-center font-semibold text-white" aria-label="Cantidad" aria-live="polite" data-cart-quantity>{{ $line->quantity }}</span>
                                    <form method="POST" action="{{ route('cart.update', $line->item->cartKey()) }}" data-cart-step="1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ min($line->quantity + 1, \App\Support\Cart::MAX_QUANTITY) }}">
                                        <button data-cart-plus class="grid size-9 place-items-center rounded-full text-lg text-slate-300 hover:text-white disabled:opacity-30"
                                                @disabled($line->quantity >= \App\Support\Cart::MAX_QUANTITY) aria-label="Agregar uno">+</button>
                                    </form>
                                </div>

                                <div class="w-24 text-right">
                                    @if ($line->price->hasDiscount())
                                        <p class="text-xs text-slate-500 line-through" data-cart-original>{{ $currency->format($line->price->original * $line->quantity) }}</p>
                                    @endif
                                    <p class="font-display text-lg font-bold text-cyan-400" data-cart-subtotal>{{ $currency->format($line->subtotal()) }}</p>
                                </div>

                                <form method="POST" action="{{ route('cart.destroy', $line->item->cartKey()) }}" data-cart-remove>
                                    @csrf
                                    @method('DELETE')
                                    <button class="grid size-9 place-items-center rounded-full text-slate-500 transition hover:bg-rose-500/10 hover:text-rose-400" aria-label="Quitar {{ $line->item->cartTitle() }}">
                                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/></svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('home') }}#catalogo" class="inline-flex items-center gap-1.5 text-sm font-medium text-cyan-400 hover:text-cyan-300">+ Seguir agregando plataformas</a>
                    </li>
                </ul>

                {{-- Resumen y datos --}}
                <form method="POST" action="{{ route('orders.store') }}" data-order-form class="rounded-3xl bg-shop-card p-6 ring-1 ring-white/10 sm:p-7 lg:sticky lg:top-24">
                    @csrf
                    <h2 class="font-display text-xl font-bold text-white">Resumen del pedido</h2>

                    <div class="mt-5 flex items-baseline justify-between border-b border-white/10 pb-5">
                        <span>Total</span>
                        <span class="font-display text-3xl font-bold text-white"><span data-cart-total>{{ $currency->format($total) }}</span> <span class="text-sm font-medium text-slate-400">{{ $currency->value }}</span></span>
                    </div>

                    @if ($hasUnavailable)
                        <p class="mt-4 rounded-xl bg-rose-500/10 px-4 py-2.5 text-xs text-rose-200 ring-1 ring-rose-500/30">Hay productos agotados que no se incluirán en el pedido.</p>
                    @endif

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="customer_name" class="mb-1.5 block text-sm font-medium text-white">Tu nombre</label>
                            <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="80" autocomplete="name"
                                   class="w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-cyan-400/60 focus:outline-none"
                                   placeholder="Ej. Laura Gómez">
                            @error('customer_name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-white">Tu WhatsApp</label>
                            <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="20" inputmode="tel" autocomplete="tel"
                                   class="w-full rounded-xl border-0 bg-white/5 px-4 py-3 text-white ring-1 ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-cyan-400/60 focus:outline-none"
                                   placeholder="Ej. 300 123 4567">
                            @error('customer_phone') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if ($canOrder)
                        <button data-cart-submit @disabled(! $lines->contains->isPurchasable())
                                class="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-[#25d366] px-6 py-4 font-bold text-white shadow-lg shadow-[#25d366]/25 transition hover:-translate-y-0.5 hover:bg-[#1ebe5b] disabled:pointer-events-none disabled:opacity-40">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8 0-1.4.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.6-.1 1Z"/></svg>
                            Enviar pedido por WhatsApp
                        </button>
                    @else
                        <p class="mt-6 text-center text-sm text-slate-400">Los pedidos por WhatsApp estarán disponibles muy pronto.</p>
                    @endif

                    <ol class="mt-6 space-y-2 border-t border-white/10 pt-5 text-xs text-slate-400">
                        <li><span class="font-semibold text-cyan-300">1.</span> Se crea tu orden con un número.</li>
                        <li><span class="font-semibold text-cyan-300">2.</span> Se abre WhatsApp con el pedido ya escrito: solo dale enviar.</li>
                        <li><span class="font-semibold text-cyan-300">3.</span> Te respondemos con los datos de pago y, al pagar, te entregamos tus accesos.</li>
                    </ol>
                </form>
            </div>
        @endif
    </div>
</x-layouts.store>
