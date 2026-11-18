<x-layouts.admin title="Monedas y ajustes">
    <div class="card mb-6 max-w-2xl">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wider">Tasa en uso</p>
                <p class="mt-2 text-2xl font-bold text-mist-100">1 USD = {{ \App\Enums\Currency::COP->format($exchangeRate->current()) }} COP</p>
                <p class="form-hint">
                    @if ($exchangeRate->isAutomatic())
                        Automática, tasa de mercado en vivo (la misma que muestra Google).
                        @if ($exchangeRate->updatedAt())
                            Actualizada {{ $exchangeRate->updatedAt()->locale('es')->diffForHumans() }} · fuente: {{ $exchangeRate->source() }}.
                        @endif
                        Se refresca sola cada hora.
                    @else
                        Manual: no cambia hasta que la edites abajo.
                    @endif
                </p>
            </div>
            @if ($exchangeRate->isAutomatic())
                <form method="POST" action="{{ route('admin.settings.rate') }}">
                    @csrf
                    <button class="btn-ghost px-2.5 py-1 text-xs">Actualizar ahora</button>
                </form>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl space-y-5">
        @csrf
        @method('PUT')

        <x-field name="store_name" label="Nombre de la tienda" :value="$settings['store_name']" required maxlength="60" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-select name="rate_mode" label="Tasa del dólar" :value="$settings['rate_mode']"
                      :options="['auto' => 'Automática (mercado / Google)', 'manual' => 'Manual']"
                      hint="Automática sigue el precio real del dólar." />
            <div data-manual-rate @class(['hidden' => old('rate_mode', $settings['rate_mode']) !== 'manual'])>
                <x-field name="usd_to_cop" label="Tasa manual: 1 USD en pesos" type="number" step="0.01" min="1"
                         :value="$settings['usd_to_cop']"
                         hint="Solo se usa en modo manual." />
            </div>
        </div>

        <x-select name="default_currency" label="Moneda por defecto" :value="$settings['default_currency']"
                  :options="collect(\App\Enums\Currency::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])"
                  hint="El cliente puede cambiarla en la tienda. Los planes con precio en COP fijo no usan la tasa." />

        <x-field name="whatsapp" label="WhatsApp de ventas" :value="$settings['whatsapp']" placeholder="+57 300 123 4567"
                 hint="Con indicativo del país. Los pedidos llegarán a este número." />

        <fieldset id="alertas" class="space-y-5 border-t border-ink-700 pt-5">
            <legend class="sr-only">Recordatorios de vencimiento</legend>
            <div>
                <p class="font-semibold text-mist-100">⏰ Cuentas por vencer y recordatorios a clientes</p>
                <p class="form-hint">Cuando a una cuenta le falten estos días, sale la alerta 🔔 en el panel con un botón verde de WhatsApp: al tocarlo se le abre el chat al cliente con este mensaje.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="alert_days" label="Avisar con" type="number" min="0" max="30" :value="$settings['alert_days']"
                         hint="Días antes del vencimiento." />
                <x-field name="reminder_time" label="Hora de corte" type="time" :value="$settings['reminder_time']"
                         hint="La hora que se le dice al cliente en el mensaje." />
            </div>

            <x-field name="reminder_template" label="Mensaje de recordatorio" type="textarea" rows="8" :value="$settings['reminder_template']"
                     hint="Se reemplazan solos: {nombre} {plataforma} {perfil} {dias} {fecha} {hora}. Si lo borras todo, vuelve al mensaje original." />
        </fieldset>

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar ajustes</button>
        </div>
    </form>

    <div class="mt-4 flex max-w-2xl flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('admin.notifications.check') }}">
            @csrf
            <button class="btn-ghost">Revisar vencimientos ahora</button>
        </form>
    </div>

    <script>
        document.querySelector('[name="rate_mode"]').addEventListener('change', (event) => {
            document.querySelector('[data-manual-rate]').classList.toggle('hidden', event.target.value !== 'manual');
        });
    </script>
</x-layouts.admin>
