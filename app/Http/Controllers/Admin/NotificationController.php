<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Support\ExpiryAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(30);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            // Cuentas de los avisos, para el botón de WhatsApp de cada cliente
            'subscriptions' => Subscription::with('service')
                ->whereIn('id', $notifications->getCollection()->pluck('data.subscription_id')->filter())
                ->get()
                ->keyBy('id'),
        ]);
    }

    /**
     * Estado de la campana para actualizarla sin recargar la página.
     * Al ser un GET del panel, también dispara la revisión automática de vencimientos.
     */
    public function status(Request $request, ExpiryAlerts $alerts): JsonResponse
    {
        $latest = $request->user()->unreadNotifications()->latest()->first();
        $subscription = $latest ? Subscription::with('service')->find($latest->data['subscription_id'] ?? 0) : null;

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'expiring' => $alerts->expiringCount(),
            'latest' => $latest ? [
                'id' => $latest->id,
                'message' => $latest->data['message'] ?? 'Cuenta por vencer',
                'url' => route('admin.notifications.open', $latest->id),
                'phone' => $subscription?->formattedPhone(),
                'whatsapp' => $subscription?->reminderUrl(),
            ] : null,
        ]);
    }

    /** Marca el aviso como leído y lleva a la cuenta del cliente. */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $subscriptionId = $notification->data['subscription_id'] ?? null;

        return $subscriptionId
            ? redirect()->route('admin.subscriptions.index', ['servicio' => $notification->data['service_id'] ?? null, 'estado' => 'vence-pronto'])
            : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return back()->with('status', 'Todas las notificaciones quedaron como leídas.');
    }

    /** Revisa ya mismo, sin esperar a la próxima hora. */
    public function check(ExpiryAlerts $alerts): RedirectResponse
    {
        $count = $alerts->run()->count();

        return back()->with('status', $count > 0
            ? "Se generaron {$count} avisos nuevos."
            : 'No hay cuentas nuevas por vencer.');
    }
}
