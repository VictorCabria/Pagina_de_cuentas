<x-layouts.admin :title="$purchase->exists ? 'Editar compra' : 'Anotar compra'">
    <form method="POST"
          action="{{ $purchase->exists ? route('admin.purchases.update', $purchase) : route('admin.purchases.store') }}"
          class="card max-w-2xl space-y-5">
        @csrf
        @if ($purchase->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-select name="service_id" label="Plataforma" :value="$purchase->service_id" placeholder="Otro gasto (escríbelo abajo)"
                      :options="$services->pluck('name', 'id')" />
            <x-field name="description" label="Detalle" :value="$purchase->description" maxlength="120"
                     placeholder="Ej. Cuenta 4 pantallas, correo@…" hint="Obligatorio si no eliges plataforma." />
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-field name="amount" label="¿Cuánto gastaste?" type="number" step="0.01" min="0.01" inputmode="decimal" required
                     :value="$purchase->amount" placeholder="Ej. 25000" />
            <x-select name="currency" label="Moneda" :value="$purchase->currency?->value"
                      :options="collect(\App\Enums\Currency::cases())->mapWithKeys(fn ($currency) => [$currency->value => $currency->label()])" />
            <x-field name="purchased_on" label="Fecha de compra" type="date" required :value="$purchase->purchased_on?->toDateString()" />
        </div>

        <x-field name="notes" label="Notas" type="textarea" rows="2" :value="$purchase->notes" placeholder="Ej. proveedor, método de pago…" />

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.purchases.index') }}" class="btn-ghost">Volver</a>
        </div>
    </form>
</x-layouts.admin>
