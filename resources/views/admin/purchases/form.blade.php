<x-layouts.admin :title="$purchase->exists ? 'Editar compra' : 'Anotar compra'">
    <form method="POST"
          action="{{ $purchase->exists ? route('admin.purchases.update', $purchase) : route('admin.purchases.store') }}"
          class="card max-w-2xl space-y-5" data-purchase-form>
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

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="amount" label="¿Cuánto gastaste?" type="number" step="0.01" min="0.01" inputmode="decimal" required
                     :value="$purchase->amount" placeholder="Ej. 25000" />
            <x-select name="currency" label="Moneda" :value="$purchase->currency?->value"
                      :options="collect(\App\Enums\Currency::cases())->mapWithKeys(fn ($currency) => [$currency->value => $currency->label()])" />
        </div>

        <div class="grid items-start gap-5 sm:grid-cols-3">
            <x-field name="purchased_on" label="Fecha de compra" type="date" required :value="$purchase->purchased_on?->toDateString()" />
            <div>
                <x-field name="duration_months" label="¿Cuántos meses dura?" type="number" min="1" max="36" step="1" inputmode="numeric"
                         :value="$purchase->duration_months" placeholder="Ej. 6" hint="Déjalo vacío si no es una cuenta (otro gasto)." />
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ([1 => '1 mes', 3 => '3 meses', 6 => '6 meses', 12 => '1 año'] as $months => $label)
                        <button type="button" data-set-months="{{ $months }}" class="rounded-full bg-ink-700 px-2.5 py-1 text-xs text-mist-300 hover:text-mist-100">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="form-label">Fecha de vencimiento</p>
                <div class="form-input flex cursor-not-allowed items-center gap-2 border-dashed bg-ink-900/60 select-none" aria-live="polite">
                    <span aria-hidden="true">🔒</span>
                    <span data-expiry-preview data-empty="Se calcula sola"
                          @class(['font-medium', 'text-accent' => $purchase->expires_on, 'text-mist-400' => ! $purchase->expires_on])>{{ $purchase->expires_on?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') ?? 'Se calcula sola' }}</span>
                </div>
                <p class="form-hint">Se calcula sola: fecha de compra + meses.</p>
            </div>
        </div>

        <x-field name="notes" label="Notas" type="textarea" rows="2" :value="$purchase->notes" placeholder="Ej. proveedor, método de pago…" />

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.purchases.index') }}" class="btn-ghost">Volver</a>
        </div>
    </form>
</x-layouts.admin>
