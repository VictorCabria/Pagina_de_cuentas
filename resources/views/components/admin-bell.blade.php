{{-- Campana de notificaciones del panel: avisos de cuentas por vencer, actualizada en vivo --}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->latest()->limit(8)->get();
    $unreadCount = $user->unreadNotifications()->count();
    $alerts = app(\App\Support\ExpiryAlerts::class);
    $expiring = $alerts->expiringCount();
    // Cuentas de los avisos, para el botón de WhatsApp de cada cliente
    $subscriptions = \App\Models\Subscription::with('service')
        ->whereIn('id', $unread->pluck('data.subscription_id')->filter())
        ->get()
        ->keyBy('id');
    $latestSubscription = $subscriptions->get($unread->first()?->data['subscription_id'] ?? 0);
@endphp

<div class="relative" data-bell data-status-url="{{ route('admin.notifications.status') }}" data-count="{{ $unreadCount }}" data-expiring="{{ $expiring }}">
    <button type="button" data-bell-toggle aria-expanded="false" aria-controls="bell-panel"
            aria-label="Notificaciones{{ $unreadCount ? ' ('.$unreadCount.' sin leer)' : '' }}"
            @class(['bell-button relative grid size-10 place-items-center rounded-full border transition', 'has-unread' => $unreadCount])>
        <svg data-bell-icon @class(['size-5', 'bell-ring' => $unreadCount]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.585 24.585 0 0 1-4.831-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" clip-rule="evenodd"/></svg>
        <span data-bell-badge @class(['absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full bg-pink-500 px-1 text-[11px] font-bold text-white ring-2 ring-ink-950', 'hidden' => ! $unreadCount])>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
    </button>

    <div id="bell-panel" data-bell-panel hidden
         class="absolute right-0 z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-ink-700 bg-ink-900 shadow-2xl shadow-black/50">
        <div class="flex items-center justify-between border-b border-ink-700 px-4 py-3">
            <p class="font-semibold text-mist-100">Notificaciones</p>
            <form method="POST" action="{{ route('admin.notifications.read-all') }}" data-bell-read-all @class(['hidden' => ! $unreadCount])>
                @csrf
                <button class="text-xs text-accent hover:underline">Marcar todas como leídas</button>
            </form>
        </div>

        <p data-bell-empty @class(['px-4 py-8 text-center text-sm', 'hidden' => $unread->isNotEmpty()])>🎉 No tienes avisos pendientes.</p>
        <ul data-bell-list class="max-h-96 divide-y divide-ink-700 overflow-y-auto">
            @foreach ($unread as $notification)
                @php
                    $subscription = $subscriptions->get($notification->data['subscription_id'] ?? 0);
                @endphp
                <li data-id="{{ $notification->id }}" class="flex gap-3 px-4 py-3 text-sm transition hover:bg-ink-800">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-pink-500/15 text-base" aria-hidden="true">⏰</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.notifications.open', $notification->id) }}" class="block text-mist-100 hover:text-accent">{{ $notification->data['message'] ?? 'Cuenta por vencer' }}</a>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            @if ($subscription)
                                <x-whatsapp-reminder :subscription="$subscription" />
                            @endif
                            <span class="text-xs text-mist-400">{{ $notification->created_at->locale('es')->diffForHumans() }}</span>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('admin.notifications.index') }}" class="block border-t border-ink-700 px-4 py-2.5 text-center text-xs font-medium text-accent hover:bg-ink-800">Ver todas las notificaciones</a>
    </div>

    {{-- Aviso flotante abajo a la derecha: sale en cualquier pantalla mientras haya cuentas por vencer.
         Dura 20 s; con la ✕ no vuelve a salir hasta que cambie algo (otra cuenta por vencer o un aviso nuevo). --}}
    <div data-bell-toast data-latest="{{ $unread->first()?->id }}" role="status" aria-live="polite"
         class="bell-toast fixed right-4 bottom-4 z-50 w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-pink-400/40 bg-ink-900 shadow-2xl shadow-black/60 sm:right-6 sm:bottom-6">
        <div class="flex items-start gap-4 p-4">
            <span class="relative grid size-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 shadow-lg shadow-pink-900/40">
                <span class="font-sans text-3xl leading-none font-extrabold text-white tabular-nums" data-bell-toast-count>{{ $expiring }}</span>
                <span class="absolute -top-1.5 -right-1.5 text-base" aria-hidden="true">⏰</span>
            </span>
            <div class="min-w-0 flex-1 text-sm">
                <p class="font-semibold text-mist-100" data-bell-toast-title>{{ $expiring === 1 ? 'Cuenta por vencer' : 'Cuentas por vencer' }}</p>
                <p class="text-xs text-pink-300">en los próximos {{ $alerts->days() }} días</p>
                <p class="mt-1.5 line-clamp-2 text-mist-400" data-bell-toast-message>{{ $unread->first()?->data['message'] }}</p>
                <div class="mt-2.5 flex flex-wrap items-center gap-2">
                    <a data-bell-toast-whatsapp href="{{ $latestSubscription?->reminderUrl() ?? '#' }}" target="_blank" rel="noopener"
                       @class(['inline-flex items-center gap-1.5 rounded-full bg-[#25d366] px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-[#1ebe5b]', 'hidden' => ! $latestSubscription?->reminderUrl()])
                       title="Enviarle el recordatorio por WhatsApp">
                        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12.04 2Zm5.2 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8 0-1.4.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.6-.1 1Z"/></svg>
                        <span data-bell-toast-phone>{{ $latestSubscription?->formattedPhone() }}</span>
                    </a>
                    <a href="{{ route('admin.subscriptions.index', ['estado' => 'vence-pronto']) }}" class="text-xs font-semibold text-accent hover:underline">Ver cuentas →</a>
                </div>
            </div>
            <button type="button" data-bell-toast-close class="-mt-1 -mr-1 grid size-7 place-items-center rounded-full text-mist-400 hover:bg-ink-700 hover:text-mist-100" aria-label="Cerrar aviso">✕</button>
        </div>
        <div class="h-1 bg-ink-700"><div class="bell-toast-progress h-full bg-gradient-to-r from-pink-500 to-rose-500"></div></div>
    </div>
</div>
