<x-layouts.admin :title="$promotion->exists ? 'Editar promoción' : 'Nueva promoción'">
    <form method="POST"
          action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}"
          class="card max-w-2xl space-y-5" data-promo-form>
        @csrf
        @if ($promotion->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-[1fr_9rem]">
            <x-field name="name" label="Nombre" :value="$promotion->name" required maxlength="100" placeholder="Black Friday" />
            <x-field name="percent" label="Descuento %" type="number" min="1" max="100" required :value="$promotion->percent"
                     hint="1 a 100. 100 = gratis." />
        </div>

        <fieldset>
            <legend class="form-label">¿A qué se aplica?</legend>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (['all' => 'Toda la tienda', 'category' => 'Una categoría', 'service' => 'Un servicio', 'plan' => 'Un plan'] as $value => $text)
                    <label class="flex cursor-pointer items-center gap-2 rounded-md border border-ink-600 px-3 py-2 text-sm has-checked:border-accent has-checked:bg-accent-soft has-checked:text-accent">
                        <input type="radio" name="scope" value="{{ $value }}" @checked(old('scope', $scope) === $value) class="sr-only">
                        {{ $text }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div data-scope-target="category">
            <x-select name="category_id" label="Categoría" :value="$promotion->category_id" placeholder="Elige una categoría"
                      :options="$categories->pluck('name', 'id')" />
        </div>
        <div data-scope-target="service">
            <x-select name="service_id" label="Servicio" :value="$promotion->service_id" placeholder="Elige un servicio"
                      :options="$services->pluck('name', 'id')" />
        </div>
        <div data-scope-target="plan">
            <x-select name="plan_id" label="Plan" :value="$promotion->plan_id" placeholder="Elige un plan"
                      :options="$plans->mapWithKeys(fn ($p) => [$p->id => $p->service->name.' · '.$p->name.' ('.$p->durationLabel().')'])" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="starts_at" label="Empieza" type="datetime-local"
                     :value="$promotion->starts_at?->format('Y-m-d\TH:i')" hint="Vacío = desde ya." />
            <x-field name="ends_at" label="Termina" type="datetime-local"
                     :value="$promotion->ends_at?->format('Y-m-d\TH:i')" hint="Vacío = sin fecha de fin." />
        </div>

        <x-toggle name="is_active" label="Activa" hint="Puedes pausarla sin borrarla." :checked="$promotion->is_active" />

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.promotions.index') }}" class="btn-ghost">Cancelar</a>
        </div>
    </form>
</x-layouts.admin>
