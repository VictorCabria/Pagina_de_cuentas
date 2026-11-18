<x-layouts.admin :title="$combo->exists ? $combo->name : 'Nuevo combo'">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $combo->exists ? route('admin.combos.update', $combo) : route('admin.combos.store') }}"
          class="card space-y-5">
        @csrf
        @if ($combo->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="name" label="Nombre" :value="$combo->name" required maxlength="100" placeholder="Combo Diamante" />
            <x-field name="description" label="Descripción corta" :value="$combo->description" maxlength="255"
                     placeholder="1 pantalla de cada plataforma por 30 días." />
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-field name="price_usd" label="Precio en USD" type="number" step="0.01" min="0" required :value="$combo->price_usd"
                     hint="Se convierte a pesos con la tasa del día." />
            <x-field name="price_cop" label="Precio fijo en COP (opcional)" type="number" step="100" min="0" :value="$combo->price_cop"
                     hint="Si lo llenas, se usa este y no la tasa." />
            <x-field name="duration_months" label="Duración (meses)" type="number" min="1" max="36" required :value="$combo->duration_months" />
        </div>

        {{-- Planes incluidos --}}
        <fieldset>
            <legend class="form-label">Planes incluidos</legend>
            <p class="form-hint -mt-1 mb-3">Elige al menos dos. El ahorro se calcula contra lo que costarían por separado.</p>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($services as $service)
                    <div class="rounded-lg border border-ink-700 bg-ink-950/40 p-3">
                        <p class="mb-2 flex items-center gap-2 text-sm font-semibold text-mist-100">
                            <x-service-logo :service="$service" class="size-6 rounded-md text-[10px]" />
                            {{ $service->name }}
                        </p>
                        <div class="space-y-1.5">
                            @foreach ($service->plans as $plan)
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" name="plans[]" value="{{ $plan->id }}" @checked(in_array($plan->id, $selected))
                                           class="rounded border-ink-600 bg-ink-950 text-accent focus:ring-accent/30">
                                    {{ $plan->name }} · {{ $plan->durationLabel() }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('plans')<p class="mt-2 text-xs text-rose-400">{{ $message }}</p>@enderror
        </fieldset>

        <div>
            <label for="image" class="form-label">Imagen del combo (opcional)</label>
            <div class="flex items-center gap-4">
                @if ($combo->exists)
                    <x-sellable-logo :item="$combo" class="size-14 text-[10px]" />
                @endif
                <input id="image" name="image" type="file" accept="image/*" class="form-input">
            </div>
            <p class="form-hint">Banner horizontal (16:9), máx. 4 MB. Sin imagen se arma uno con los logos de las plataformas.</p>
            @if ($combo->image)
                <label class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_image" value="1"> Quitar imagen</label>
            @endif
            @error('image')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="slug" label="Slug (URL)" :value="$combo->slug" hint="Vacío = se genera del nombre." />
            <x-field name="sort_order" label="Orden" type="number" min="0" :value="$combo->sort_order" />
        </div>

        <div class="grid gap-4 border-t border-ink-700 pt-5 sm:grid-cols-3">
            <x-toggle name="is_available" label="Disponible" hint="Apágalo cuando no haya cupos." :checked="$combo->is_available" />
            <x-toggle name="is_active" label="Visible en la tienda" :checked="$combo->is_active" />
            <x-toggle name="is_featured" label="Más popular" hint="Lleva una etiqueta destacada." :checked="$combo->is_featured" />
        </div>

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.combos.index') }}" class="btn-ghost">Volver</a>
        </div>
    </form>
</x-layouts.admin>
