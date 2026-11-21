<x-layouts.admin :title="$subscription->exists ? 'Cuenta de '.$subscription->customer_name : 'Agregar cuenta'">
    <form method="POST"
          action="{{ $subscription->exists ? route('admin.subscriptions.update', $subscription) : route('admin.subscriptions.store') }}"
          class="card max-w-3xl space-y-5" data-subscription-form
          data-password-url="{{ route('admin.subscriptions.known-password') }}"
          data-emails-url="{{ route('admin.subscriptions.known-emails') }}">
        @csrf
        @if ($subscription->exists)
            @method('PUT')
        @endif

        @if ($subscription->order)
            <p class="rounded-md bg-accent-soft px-4 py-2.5 text-sm text-accent">
                Viene de la <a href="{{ route('admin.orders.show', $subscription->order) }}" class="font-semibold underline">orden {{ $subscription->order->number() }}</a>.
                Completa el correo, la contraseña y el PIN, y luego envíale los datos al cliente.
            </p>
        @endif

        <x-select name="service_id" label="Plataforma" :value="$subscription->service_id" placeholder="Elige una plataforma" required
                  :options="$services->pluck('name', 'id')" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="customer_name" label="Persona" :value="$subscription->customer_name" required maxlength="80" placeholder="Nombre del cliente" />
            <x-field name="customer_phone" label="Número (WhatsApp)" :value="$subscription->formattedPhone()" placeholder="300 123 4567" inputmode="tel" />
        </div>

        <div class="grid gap-5 border-t border-ink-700 pt-5 sm:grid-cols-3">
            <div class="sm:col-span-3">
                <x-field name="account_email" label="Cuenta (correo)" :value="$subscription->account_email" list="account-emails" autocomplete="off"
                         placeholder="correo@ejemplo.com" hint="Si eliges un correo que ya usaste en esta plataforma, se llena la contraseña." />
                <datalist id="account-emails"></datalist>
            </div>
            {{-- La clave y el PIN guardados no viajan en la página: se piden con "Mostrar" --}}
            @php($hasSecrets = $subscription->exists && ($subscription->account_password || $subscription->pin))
            <x-field name="account_password" label="Contraseña" autocomplete="off" data-secret="password"
                     :placeholder="$subscription->exists && $subscription->account_password ? \App\Support\Mask::SECRET.' guardada' : null"
                     :hint="$subscription->exists && $subscription->account_password ? 'Déjala vacía para no cambiarla.' : null" />
            <x-field name="profile_name" label="Perfil" :value="$subscription->profile_name" maxlength="40" placeholder="Ej. Perfil 3, PIXEL" />
            <x-field name="pin" label="PIN del perfil" maxlength="20" data-secret="pin"
                     :placeholder="$subscription->exists && $subscription->pin ? \App\Support\Mask::SECRET.' guardado' : 'Ej. 1818'"
                     :hint="$subscription->exists && $subscription->pin ? 'Déjalo vacío para no cambiarlo.' : null" />
            @if ($hasSecrets)
                <div class="sm:col-span-3" data-secrets data-secrets-url="{{ route('admin.subscriptions.secrets', $subscription) }}">
                    <button type="button" data-reveal class="text-xs font-medium text-accent hover:underline">👁 Mostrar clave y PIN guardados</button>
                </div>
            @endif
        </div>

        <div class="grid gap-5 border-t border-ink-700 pt-5 sm:grid-cols-2">
            <x-field name="starts_on" label="Inicio" type="date" required :value="$subscription->starts_on?->toDateString()" />
            <div>
                <x-field name="expires_on" label="Expira" type="date" required :value="$subscription->expires_on?->toDateString()" />
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ([1 => '1 mes', 3 => '3 meses', 6 => '6 meses', 12 => '1 año'] as $months => $label)
                        <button type="button" data-add-months="{{ $months }}" class="rounded-full bg-ink-700 px-2.5 py-1 text-xs text-mist-300 hover:text-mist-100">+{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <x-field name="notes" label="Notas" type="textarea" rows="2" :value="$subscription->notes" placeholder="Ej. pagó 2$, perfil «PIXEL»…" />

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.subscriptions.index') }}" class="btn-ghost">Volver</a>
        </div>
    </form>
</x-layouts.admin>
