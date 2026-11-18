<x-layouts.admin title="Notificaciones">
    <div class="mb-6 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.notifications.check') }}">
            @csrf
            <button class="btn-ghost">Revisar vencimientos ahora</button>
        </form>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button class="btn-ghost">Marcar todas como leídas</button>
            </form>
        @endif
        <a href="{{ route('admin.settings.edit') }}#alertas" class="btn-ghost">Configurar avisos por WhatsApp</a>
    </div>

    @if ($notifications->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">No hay notificaciones todavía.</p>
            <p class="mt-1 text-sm">Cuando una cuenta de un cliente esté a {{ \App\Models\Setting::get('alert_days') }} días o menos de vencer, aparecerá aquí y en la campana 🔔.</p>
        </div>
    @else
        <ul class="divide-y divide-ink-700 overflow-hidden rounded-xl border border-ink-700 bg-ink-800/30">
            @foreach ($notifications as $notification)
                <li @class(['flex items-start gap-3 px-4 py-3.5', 'bg-pink-500/5' => $notification->unread()])>
                    <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-pink-500/15" aria-hidden="true">⏰</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-mist-100">{{ $notification->data['message'] ?? 'Cuenta por vencer' }}</p>
                        <p class="text-xs">{{ $notification->created_at->locale('es')->isoFormat('D MMM YYYY, h:mm a') }} · {{ $notification->created_at->locale('es')->diffForHumans() }}</p>
                        @if ($subscription = $subscriptions->get($notification->data['subscription_id'] ?? 0))
                            <x-whatsapp-reminder :subscription="$subscription" class="mt-2" />
                        @endif
                    </div>
                    @if ($notification->unread())
                        <x-badge color="pink">Nueva</x-badge>
                    @endif
                    <a href="{{ route('admin.notifications.open', $notification->id) }}" class="btn-ghost shrink-0 px-2.5 py-1 text-xs">Ver cuenta</a>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</x-layouts.admin>
