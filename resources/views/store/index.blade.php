<x-layouts.store :currency="$currency">
    {{-- Hero --}}
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-blue-950 via-indigo-900 to-cyan-800 px-6 py-12 text-white sm:px-12 lg:py-16">
            <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-pink-300/20 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-32 left-1/3 size-96 rounded-full bg-cyan-400/20 blur-3xl" aria-hidden="true"></div>

            <div class="relative grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-medium ring-1 ring-white/20">
                        <span class="size-1.5 rounded-full bg-pink-300"></span>
                        {{ $services->count() }} plataformas premium disponibles
                    </span>
                    <h1 class="mt-6 font-display text-4xl leading-[1.05] font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        Tus plataformas favoritas, <span class="text-cyan-300">pagando mucho menos</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-base text-cyan-50/80 sm:text-lg">
                        Streaming, música, inteligencia artificial y productividad con garantía todo el periodo y atención directa por WhatsApp.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#catalogo" class="rounded-full bg-cyan-400 px-7 py-3.5 text-sm font-bold text-cyan-950 shadow-lg shadow-cyan-500/30 transition hover:-translate-y-0.5 hover:bg-cyan-300">Ver catálogo</a>
                        <a href="#como-funciona" class="rounded-full px-7 py-3.5 text-sm font-semibold ring-1 ring-white/40 transition hover:bg-white/10">Cómo funciona</a>
                    </div>
                    <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 text-sm">
                        <div><dt class="text-cyan-50/60">Entrega</dt><dd class="font-semibold">En minutos</dd></div>
                        <div><dt class="text-cyan-50/60">Garantía</dt><dd class="font-semibold">Todo el plan</dd></div>
                        <div><dt class="text-cyan-50/60">Pagas en</dt><dd class="font-semibold">COP o USD</dd></div>
                    </dl>
                </div>

                <div class="hidden grid-cols-3 gap-4 sm:grid">
                    @foreach ($services->where('is_featured', true)->take(6) as $featured)
                        @php($featuredPrice = $featured->cheapestPrice($currency))
                        <a href="{{ route('store.service', $featured) }}"
                           @class(['rounded-2xl bg-shop-card/90 p-4 text-center shadow-xl shadow-black/40 transition hover:-translate-y-1', 'translate-y-6' => $loop->index % 3 === 1])>
                            <x-service-logo :service="$featured" class="mx-auto size-12 text-xl" />
                            <p class="mt-3 truncate text-sm font-semibold text-white">{{ $featured->name }}</p>
                            @if ($featuredPrice)
                                <p class="text-xs font-semibold text-cyan-400">{{ $currency->format($featuredPrice->perMonth()) }}/mes</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Combos --}}
    @if ($combos->isNotEmpty())
        <section id="combos" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-cyan-400">Planes combo</p>
                    <h2 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Junta tus plataformas y ahorra</h2>
                </div>
                <p class="max-w-sm text-sm text-slate-400">Varias plataformas en un solo pedido, por menos de lo que cuestan por separado.</p>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($combos as $combo)
                    <x-combo-card :combo="$combo" :currency="$currency" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Licencias --}}
    @if ($licenses->isNotEmpty())
        <section id="licencias" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-cyan-400">🔑 Licencias</p>
                    <h2 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Software profesional original</h2>
                </div>
                <p class="max-w-sm text-sm text-slate-400">Autodesk, Microsoft 365, Adobe Creative Cloud y más, activados en tu propia cuenta.</p>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($licenses as $license)
                    <x-service-card :service="$license" :currency="$currency" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Catálogo --}}
    <section id="catalogo" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6" data-catalog>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-cyan-400">Catálogo</p>
                <h2 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Elige tu plataforma</h2>
            </div>
            <p class="text-sm text-slate-400">Precios en <strong class="text-white">{{ $currency->label() }}</strong></p>
        </div>

        <div class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:px-0" role="tablist" aria-label="Categorías">
            <button type="button" data-filter="all" role="tab" aria-selected="true"
                    class="filter-tab is-active">Todos</button>
            @foreach ($categories as $category)
                <button type="button" data-filter="{{ $category->slug }}" role="tab" aria-selected="false"
                        class="filter-tab">{{ $category->icon }} {{ $category->name }}</button>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($services as $service)
                <x-service-card :service="$service" :currency="$currency" />
            @endforeach
        </div>

        <p data-empty class="mt-6 hidden rounded-2xl bg-shop-card p-10 text-center text-slate-400 ring-1 ring-white/10">
            No encontramos ese servicio. Prueba con otro nombre o escríbenos y lo conseguimos.
        </p>
    </section>

    {{-- Beneficios --}}
    <section class="mx-auto max-w-7xl px-4 pt-20 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Entrega rápida', 'Recibes tus accesos por WhatsApp en minutos después de confirmar el pago.', 'M13 2 3 14h9l-1 8 10-12h-9l1-8Z'],
                ['Garantía total', 'Si algo falla durante tu plan, lo resolvemos o te reponemos el acceso.', 'M12 2 4 5v6c0 5 3.4 9.6 8 11 4.6-1.4 8-6 8-11V5l-8-3Zm-1.2 13.6-3.5-3.5 1.4-1.4 2.1 2.1 4.6-4.6 1.4 1.4-6 6Z'],
                ['Precio justo', 'Pagas una fracción del precio oficial. En pesos con la tasa real del día.', 'M12 1a11 11 0 1 0 0 22 11 11 0 0 0 0-22Zm1 17.9V20h-2v-1.1c-1.7-.3-3-1.4-3.1-3h2c.1.9.9 1.5 2.1 1.5 1.3 0 2-.6 2-1.4 0-.8-.6-1.2-2.2-1.6-2.1-.5-3.4-1.2-3.4-3 0-1.4 1.1-2.5 2.6-2.8V7h2v1.1c1.6.3 2.6 1.4 2.7 2.8h-2c-.1-.8-.7-1.3-1.8-1.3-1.1 0-1.7.5-1.7 1.2 0 .7.6 1.1 2.2 1.5 2.2.5 3.4 1.3 3.4 3.1 0 1.5-1.1 2.6-2.8 2.9Z'],
                ['Soporte humano', 'Te atiende una persona real por WhatsApp, antes y después de comprar.', 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm-4 8.5h8v1.5H8v-1.5Zm0 3h5.5V15H8v-1.5Zm0-6h8V9H8V7.5Z'],
            ] as [$benefitTitle, $benefitText, $benefitIcon])
                <div class="rounded-2xl bg-shop-card p-6 ring-1 ring-white/10">
                    <span class="grid size-11 place-items-center rounded-xl bg-cyan-500/10 text-cyan-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $benefitIcon }}"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-white">{{ $benefitTitle }}</h3>
                    <p class="mt-1.5 text-sm">{{ $benefitText }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section id="como-funciona" class="mx-auto max-w-7xl px-4 pt-20 sm:px-6">
        <div class="text-center">
            <p class="text-sm font-semibold text-cyan-400">Cómo funciona</p>
            <h2 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Listo en tres pasos</h2>
        </div>
        <ol class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Elige tu plan', 'Escoge la plataforma y cuántos meses quieres. Verás el precio final, con descuentos incluidos.'],
                ['Pide por WhatsApp', 'Te llevamos al chat con tu pedido ya escrito. Te enviamos los datos de pago.'],
                ['Disfruta', 'Recibes tus accesos y empiezas a usarlo de inmediato, con garantía todo el periodo.'],
            ] as [$stepTitle, $stepText])
                <li class="relative rounded-2xl bg-shop-card p-7 ring-1 ring-white/10">
                    <span class="font-display text-5xl font-bold text-cyan-500/25">{{ $loop->iteration }}</span>
                    <h3 class="mt-2 text-lg font-semibold text-white">{{ $stepTitle }}</h3>
                    <p class="mt-1.5 text-sm">{{ $stepText }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Preguntas frecuentes --}}
    <section id="preguntas" class="mx-auto max-w-3xl px-4 pt-20 sm:px-6">
        <div class="text-center">
            <p class="text-sm font-semibold text-cyan-400">Preguntas frecuentes</p>
            <h2 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Resolvemos tus dudas</h2>
        </div>
        <div class="mt-10 space-y-3">
            @foreach ([
                ['¿Es seguro comprar aquí?', 'Sí. Cada plan tiene garantía durante todo el periodo: si el acceso deja de funcionar, lo reponemos sin costo.'],
                ['¿Cuánto tarda la entrega?', 'Normalmente unos minutos después de confirmar tu pago, dentro del horario de atención.'],
                ['¿Qué tasa usan para los precios en pesos?', 'La tasa de mercado del dólar del día (la misma que ves en Google), actualizada cada hora. Puedes ver los precios en COP o USD con el selector de arriba.'],
                ['¿Cómo pago?', 'Al pedir por WhatsApp te compartimos los medios de pago disponibles en Colombia y Panamá.'],
                ['¿Puedo renovar mi plan?', 'Sí. Escríbenos antes de que termine y lo renovamos manteniendo tu perfil.'],
            ] as [$question, $answer])
                <details class="group rounded-2xl bg-shop-card px-6 py-5 ring-1 ring-white/10 open:ring-cyan-500/50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-white">
                        {{ $question }}
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-cyan-500/10 text-cyan-400 transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts.store>
