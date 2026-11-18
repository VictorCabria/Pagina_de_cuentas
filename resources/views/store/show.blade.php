<x-layouts.store :currency="$currency" :title="$service->name" :description="$service->tagline">
    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
        <nav class="text-sm text-slate-500" aria-label="Ruta">
            <a href="{{ route('home') }}" class="hover:text-cyan-300">Inicio</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('home') }}#catalogo" class="hover:text-cyan-300">{{ $service->category?->name ?? 'Catálogo' }}</a>
            <span class="mx-1.5">/</span>
            <span class="text-slate-300">{{ $service->name }}</span>
        </nav>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1.1fr]">
            {{-- Información del servicio --}}
            <div class="rounded-3xl bg-shop-card p-7 ring-1 ring-white/10 sm:p-9">
                <div class="flex items-center gap-5">
                    <x-service-logo :service="$service" class="size-20 text-3xl" />
                    <div>
                        <h1 class="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $service->name }}</h1>
                        <p class="mt-1 text-slate-400">{{ $service->tagline }}</p>
                    </div>
                </div>

                @if ($service->description)
                    <p class="mt-6 leading-relaxed">{!! nl2br(e($service->description)) !!}</p>
                @endif

                @if ($service->features)
                    <ul class="mt-6 space-y-3">
                        @foreach ($service->features as $feature)
                            <li class="flex items-center gap-3 text-slate-200">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-cyan-500/15 text-cyan-400">
                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-8 grid grid-cols-3 gap-3 border-t border-white/10 pt-6 text-center text-xs">
                    <div class="rounded-xl bg-white/5 p-3"><p class="font-semibold text-white">Entrega</p><p>en minutos</p></div>
                    <div class="rounded-xl bg-white/5 p-3"><p class="font-semibold text-white">Garantía</p><p>todo el periodo</p></div>
                    <div class="rounded-xl bg-white/5 p-3"><p class="font-semibold text-white">Soporte</p><p>por WhatsApp</p></div>
                </div>
            </div>

            {{-- Selector de plan --}}
            <div class="rounded-3xl bg-shop-card p-7 ring-1 ring-white/10 sm:p-9" data-plan-picker>
                <h2 class="font-display text-xl font-bold text-white">Elige tu plan</h2>
                <p class="mt-1 text-sm text-slate-400">Precios en {{ $currency->label() }}.</p>

                @unless ($service->inStock())
                    <div class="mt-5 flex items-center gap-3 rounded-2xl bg-rose-500/10 px-4 py-3 ring-1 ring-rose-500/40" role="status">
                        <span class="rounded-full bg-rose-500 px-2.5 py-1 text-[11px] font-extrabold tracking-widest text-white uppercase">Agotado</span>
                        <p class="text-sm text-rose-200">Este servicio no tiene cupos en este momento. Escríbenos y te avisamos cuando vuelva.</p>
                    </div>
                @endunless

                @php
                    $firstAvailable = $service->is_available ? $service->plans->firstWhere('is_available', true) : null;
                @endphp

                <form method="POST" action="{{ route('cart.store') }}">
                @csrf
                <div class="mt-6 space-y-3">
                    @forelse ($service->plans as $plan)
                        @php
                            $price = $plan->price($currency);
                            $purchasable = $plan->purchasable();
                        @endphp
                        <label @class([
                            'relative flex cursor-pointer items-center gap-4 rounded-2xl p-4 ring-1 transition has-checked:bg-cyan-500/10 has-checked:ring-2 has-checked:ring-cyan-500',
                            'ring-white/10 hover:ring-cyan-500/50' => $purchasable,
                            'cursor-not-allowed opacity-50 ring-white/10' => ! $purchasable,
                        ])>
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" class="size-4 accent-cyan-600"
                                   data-total="{{ $price->formatted() }}"
                                   @checked($firstAvailable?->is($plan)) @disabled(! $purchasable)>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-white">{{ $plan->durationLabel() }}</span>
                                <span class="block text-sm text-slate-400">{{ $plan->name }}
                                    @if ($plan->duration_months > 1 && ! $price->isFree())
                                        · {{ $price->formattedPerMonth() }}/mes
                                    @endif
                                </span>
                            </span>
                            <span class="text-right">
                                @if (! $purchasable)
                                    <span class="rounded-full bg-rose-500/15 px-2.5 py-1 text-xs font-bold text-rose-400 uppercase">Agotado</span>
                                @else
                                    @if ($price->hasDiscount())
                                        <span class="mb-0.5 inline-block rounded-full bg-pink-400 px-2 py-0.5 text-[11px] font-bold text-pink-950">-{{ $price->discount }}%</span>
                                        <span class="block text-xs text-slate-500 line-through">{{ $price->formattedOriginal() }}</span>
                                    @endif
                                    <span class="block font-display text-lg font-bold text-cyan-400">{{ $price->formatted() }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="rounded-2xl bg-white/5 p-6 text-center text-sm">Este servicio todavía no tiene planes.</p>
                    @endforelse
                </div>

                <div class="mt-6 rounded-2xl bg-white/5 p-5">
                    @if ($firstAvailable)
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm">Total a pagar</span>
                            <span class="font-display text-2xl font-bold text-white" data-total>{{ $firstAvailable->price($currency)->formatted() }}</span>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <button class="flex items-center justify-center gap-2 rounded-full bg-cyan-400 px-6 py-3.5 font-semibold text-cyan-950 shadow-lg shadow-cyan-500/25 transition hover:bg-cyan-300">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                Agregar al carrito
                            </button>
                            <button name="checkout" value="1"
                                    class="rounded-full px-6 py-3.5 font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/5 hover:ring-cyan-400/60">
                                Pedir ahora
                            </button>
                        </div>
                        <p class="mt-3 text-center text-xs text-slate-500">Sin pagos en línea: envías tu pedido por WhatsApp y te pasamos los datos de pago.</p>
                    @else
                        <p class="text-center text-sm font-medium text-slate-400">Sin cupos disponibles por ahora. Vuelve pronto.</p>
                    @endif
                </div>
                </form>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="pt-16">
                <h2 class="font-display text-2xl font-bold tracking-tight text-white">También te puede gustar</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $relatedService)
                        <x-service-card :service="$relatedService" :currency="$currency" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
