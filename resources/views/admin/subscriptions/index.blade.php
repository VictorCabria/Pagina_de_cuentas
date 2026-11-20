<x-layouts.admin title="Cuentas">
    <x-slot:actions>
        @if ($counts['todas'] > 0)
            <a href="{{ route('admin.subscriptions.export') }}" class="btn-ghost">Exportar a Excel</a>
        @endif
        <a href="{{ route('admin.subscriptions.create', array_filter(['servicio' => $serviceId])) }}" class="btn-primary">+ Agregar cuenta</a>
    </x-slot:actions>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['todas' => 'Todas', 'falta-correo' => 'Falta correo', 'vence-pronto' => 'Vence pronto', 'vencidas' => 'Vencidas'] as $key => $label)
            @php($active = $key === 'todas' ? ! $filter : $filter === $key)
            <a href="{{ route('admin.subscriptions.index', array_filter(['estado' => $key === 'todas' ? null : $key, 'servicio' => $serviceId, 'q' => $search ?: null])) }}"
               @class(['rounded-full px-3.5 py-1.5 text-sm font-medium transition', 'bg-accent text-cyan-950' => $active, 'bg-ink-800 text-mist-300 hover:text-mist-100' => ! $active])>
                {{ $label }}
                <span @class(['ml-0.5', 'opacity-70' => $active || $counts[$key] === 0 || $key === 'todas', 'rounded-full bg-pink-500 px-1.5 text-[10px] font-bold text-white' => ! $active && $counts[$key] > 0 && $key !== 'todas'])>{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mb-6 flex flex-wrap gap-2">
        @if ($filter)
            <input type="hidden" name="estado" value="{{ $filter }}">
        @endif
        <select name="servicio" onchange="this.form.submit()" class="form-input w-auto" aria-label="Plataforma">
            <option value="">Todas las plataformas</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected($serviceId === $service->id)>{{ $service->name }}</option>
            @endforeach
        </select>
        <input type="search" name="q" value="{{ $search }}" placeholder="Buscar cliente, correo o número…" class="form-input min-w-0 flex-1 sm:max-w-xs">
        <button class="btn-ghost">Buscar</button>
    </form>

    @if ($groups->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">{{ $counts['todas'] === 0 ? 'Todavía no hay cuentas registradas.' : 'No hay cuentas con ese filtro.' }}</p>
            <p class="mt-1 text-sm">Cuando marques una orden como <strong class="text-mist-100">Pagada</strong>, sus cuentas aparecerán aquí con el nombre y las fechas listas. También puedes agregarlas a mano.</p>
        </div>
    @endif

    <div class="space-y-8">
        @foreach ($groups as $group)
            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2.5 text-sm font-bold tracking-wider text-mist-100 uppercase">
                        <x-service-logo :service="$group['service']" class="size-7 rounded-lg text-xs" />
                        Cuenta de {{ $group['service']->name }}
                        <span class="font-normal text-mist-400 normal-case">· {{ $group['rows']->count() }}</span>
                    </h2>
                    <a href="{{ route('admin.subscriptions.create', ['servicio' => $group['service']->id]) }}" class="text-xs font-medium text-accent hover:underline">+ Agregar</a>
                </div>

                <div class="overflow-x-auto rounded-xl border border-ink-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Persona</th>
                                <th class="px-4 py-3">Cuenta · perfil · PIN</th>
                                <th class="px-4 py-3">Vigencia</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                            @foreach ($group['rows'] as $subscription)
                                @php($status = $subscription->status())
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-mist-100">{{ $subscription->customer_name }}</p>
                                        @if ($subscription->needsReminder())
                                            {{-- Por vencer: botón verde que le manda el recordatorio --}}
                                            <x-whatsapp-reminder :subscription="$subscription" class="mt-1" />
                                        @elseif ($subscription->customer_phone)
                                            <a href="https://wa.me/{{ $subscription->customer_phone }}" target="_blank" rel="noopener" class="text-xs hover:text-accent">{{ $subscription->formattedPhone() }}</a>
                                        @endif
                                        @if ($subscription->order)
                                            <a href="{{ route('admin.orders.show', $subscription->order) }}" class="block text-[11px] text-mist-400 hover:text-accent">Orden {{ $subscription->order->number() }}</a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($subscription->account_email)
                                            <p class="text-mist-100">{{ $subscription->account_email }}</p>
                                            @if ($subscription->account_password)
                                                <p class="mt-0.5 text-xs">Clave: <span class="cursor-help font-mono text-mist-300 blur-[3px] transition hover:blur-none" title="Pasa el mouse para ver">{{ $subscription->account_password }}</span></p>
                                            @endif
                                            @if ($subscription->profile_name || $subscription->pin)
                                                <p class="mt-1.5 flex flex-wrap gap-1.5">
                                                    @if ($subscription->profile_name)
                                                        <span class="inline-flex items-center gap-1 rounded-md bg-accent-soft px-2 py-0.5 text-xs text-accent">👤 {{ $subscription->profile_name }}</span>
                                                    @endif
                                                    @if ($subscription->pin)
                                                        <span class="inline-flex items-center gap-1 rounded-md bg-ink-700 px-2 py-0.5 text-xs text-mist-100">PIN <span class="font-mono font-semibold">{{ $subscription->pin }}</span></span>
                                                    @endif
                                                </p>
                                            @endif
                                        @else
                                            <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="inline-flex items-center gap-1 rounded-md border border-dashed border-amber-400/50 px-2.5 py-1 text-xs font-medium text-amber-300 hover:bg-amber-500/10">
                                                + Poner correo
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                                        <p>{{ $subscription->starts_on->locale('es')->isoFormat('D MMM YYYY') }}</p>
                                        <p class="text-mist-100">→ {{ $subscription->expires_on->locale('es')->isoFormat('D MMM YYYY') }}</p>
                                        <p @class(['mt-0.5', 'text-rose-300' => $subscription->daysLeft() < 0, 'text-pink-300' => $subscription->daysLeft() >= 0 && $subscription->daysLeft() <= \App\Models\Subscription::EXPIRING_DAYS])>
                                            @if ($subscription->daysLeft() < 0)
                                                venció hace {{ abs($subscription->daysLeft()) }} {{ abs($subscription->daysLeft()) === 1 ? 'día' : 'días' }}
                                            @elseif ($subscription->daysLeft() === 0)
                                                vence hoy
                                            @else
                                                quedan {{ $subscription->daysLeft() }} {{ $subscription->daysLeft() === 1 ? 'día' : 'días' }}
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3"><x-badge :color="$status['color']">{{ $status['label'] }}</x-badge></td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            @if ($subscription->customer_phone && $subscription->account_email)
                                                <a href="https://wa.me/{{ $subscription->customer_phone }}?text={{ rawurlencode($subscription->credentialsMessage()) }}" target="_blank" rel="noopener"
                                                   class="btn px-2.5 py-1 text-xs bg-[#25d366]/15 text-[#4ade80] hover:bg-[#25d366]/25" title="Enviarle al cliente sus datos de acceso">Enviar datos</a>
                                            @endif
                                            <form method="POST" action="{{ route('admin.subscriptions.renew', $subscription) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn-ghost px-2.5 py-1 text-xs" title="Extender un mes">+1 mes</button>
                                            </form>
                                            <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                            <form method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}"
                                                  onsubmit="return confirm('¿Eliminar la cuenta de {{ addslashes($subscription->customer_name) }}?')">
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
            </section>
        @endforeach
    </div>
</x-layouts.admin>
