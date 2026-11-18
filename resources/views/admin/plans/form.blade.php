<x-layouts.admin :title="($plan->exists ? 'Editar plan' : 'Nuevo plan').' · '.$service->name">
    <form method="POST"
          action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store', $service) }}"
          class="card max-w-2xl space-y-5" data-plan-form data-rate="{{ $rate }}">
        @csrf
        @if ($plan->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-[1fr_10rem]">
            <x-field name="name" label="Nombre del plan" :value="$plan->name" required maxlength="100" placeholder="Perfil individual" />
            <x-field name="duration_months" label="Duración (meses)" type="number" min="1" max="60" required :value="$plan->duration_months" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="price_usd" label="Precio en dólares (USD)" type="number" step="0.01" min="0" required
                     :value="$plan->price_usd" placeholder="4.99" />
            <x-field name="price_cop" label="Precio en pesos (COP)" type="number" step="1" min="0"
                     :value="$plan->price_cop" placeholder="Automático"
                     hint="Vacío = USD × {{ number_format($rate, 0, ',', '.') }} (tasa actual), redondeado a la centena." />
        </div>
        <p class="form-hint -mt-2" data-cop-preview></p>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="sort_order" label="Orden" type="number" min="0" :value="$plan->sort_order" />
            <div class="pt-7">
                <x-toggle name="is_available" label="Disponible" hint="Apágalo cuando no haya cupos de este plan." :checked="$plan->is_available" />
            </div>
        </div>

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.services.edit', $service) }}" class="btn-ghost">Cancelar</a>
        </div>
    </form>
</x-layouts.admin>
