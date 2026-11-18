<x-layouts.admin :title="$service->exists ? $service->name : 'Nuevo servicio'">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}"
          class="card space-y-5">
        @csrf
        @if ($service->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="name" label="Nombre" :value="$service->name" required maxlength="100" placeholder="Netflix" />
            <x-select name="category_id" label="Categoría" :value="$service->category_id" placeholder="Sin categoría"
                      :options="$categories->pluck('name', 'id')" />
        </div>

        <x-field name="tagline" label="Frase corta" :value="$service->tagline" maxlength="200"
                 placeholder="Películas y series en 4K" hint="Aparece en la tarjeta de la tienda." />
        <x-field name="description" label="Descripción" type="textarea" :value="$service->description" rows="3" />
        <x-field name="features" label="Características" type="textarea" rows="4"
                 :value="implode(PHP_EOL, $service->features ?? [])"
                 placeholder="Calidad 4K UHD&#10;Perfil propio con PIN&#10;Garantía todo el periodo"
                 hint="Una por línea. Se muestran con ✓ en la tienda." />

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="color" class="form-label">Color de marca</label>
                <input id="color" name="color" type="color" value="{{ old('color', $service->color) }}"
                       class="h-10 w-full cursor-pointer rounded-md border border-ink-600 bg-ink-950/60 p-1">
                @error('color')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <x-field name="slug" label="Slug (URL)" :value="$service->slug" hint="Vacío = se genera del nombre." />
            <x-field name="sort_order" label="Orden" type="number" min="0" :value="$service->sort_order" />
        </div>

        <div>
            <label for="logo" class="form-label">Logo</label>
            <div class="flex items-center gap-4">
                @if ($service->exists)
                    <x-service-logo :service="$service" class="size-14 text-xl" />
                @endif
                <input id="logo" name="logo" type="file" accept="image/*" class="form-input">
            </div>
            <p class="form-hint">PNG o SVG con fondo transparente, máx. 2 MB.</p>
            @if ($service->logo)
                <label class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_logo" value="1"> Quitar logo</label>
            @endif
            @error('logo')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 border-t border-ink-700 pt-5 sm:grid-cols-3">
            <x-toggle name="is_available" label="Disponible" hint="Apágalo cuando no haya cupos." :checked="$service->is_available" />
            <x-toggle name="is_active" label="Visible en la tienda" :checked="$service->is_active" />
            <x-toggle name="is_featured" label="Destacado" hint="Sale primero en la portada." :checked="$service->is_featured" />
        </div>

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.services.index') }}" class="btn-ghost">Volver</a>
        </div>
    </form>

    @if ($service->exists)
        @php($pricing = app(\App\Support\Pricing::class))
        <section class="mt-10">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-mist-100">Planes</h2>
                    <p class="text-sm">Precios con la promoción vigente ya aplicada.</p>
                </div>
                <a href="{{ route('admin.plans.create', $service) }}" class="btn-primary">+ Agregar plan</a>
            </div>

            @if ($service->plans->isEmpty())
                <div class="card text-center text-sm">Este servicio aún no tiene planes: no se podrá comprar.</div>
            @else
                <div class="overflow-x-auto rounded-xl border border-ink-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Plan</th>
                                <th class="px-4 py-3">USD</th>
                                <th class="px-4 py-3">COP</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                            @foreach ($service->plans as $plan)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-mist-100">{{ $plan->name }}</p>
                                        <p class="text-xs">{{ $plan->durationLabel() }}</p>
                                    </td>
                                    @foreach ([\App\Enums\Currency::USD, \App\Enums\Currency::COP] as $cur)
                                        @php($price = $pricing->for($plan, $cur))
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-mist-100">{{ $price->formatted() }}</span>
                                            @if ($price->hasDiscount())
                                                <span class="block text-xs line-through">{{ $price->formattedOriginal() }}</span>
                                            @endif
                                            @if ($cur === \App\Enums\Currency::COP && $plan->price_cop === null)
                                                <span class="block text-[11px] text-mist-400">auto por tasa</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3">
                                        <x-stock-toggle :action="route('admin.plans.toggle', $plan)" :available="$plan->is_available" />
                                        @if ($pricing->bestPromotion($plan))
                                            <x-badge color="pink" class="mt-1">-{{ $pricing->bestPromotion($plan)->percent }}% {{ $pricing->bestPromotion($plan)->name }}</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                                                  onsubmit="return confirm('¿Eliminar el plan «{{ addslashes($plan->name) }}»?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-danger px-2.5 py-1 text-xs">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</x-layouts.admin>
