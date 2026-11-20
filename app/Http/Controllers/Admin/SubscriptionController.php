<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Subscription;
use App\Support\Phone;
use App\Support\SubscriptionsSpreadsheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    /** Filtros de estado de la lista (valor de la URL => scope). */
    private const FILTERS = [
        'falta-correo' => 'missingAccount',
        'vence-pronto' => 'expiringSoon',
        'vencidas' => 'expired',
    ];

    public function index(Request $request): View
    {
        $filter = array_key_exists((string) $request->query('estado'), self::FILTERS) ? $request->query('estado') : null;
        $search = trim((string) $request->query('q'));

        $subscriptions = Subscription::with(['service', 'order'])
            ->when($filter, fn (Builder $query) => $query->{self::FILTERS[$filter]}())
            ->when($request->integer('servicio'), fn (Builder $query, int $serviceId) => $query->where('service_id', $serviceId))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('customer_name', 'like', "%{$search}%")
                ->orWhere('account_email', 'like', "%{$search}%")
                ->when(preg_replace('/\D/', '', $search), fn (Builder $query, string $digits) => $query
                    ->orWhere('customer_phone', 'like', "%{$digits}%"))))
            ->orderBy('expires_on')
            ->get();

        return view('admin.subscriptions.index', [
            'groups' => $subscriptions->groupBy('service_id')
                ->map(fn ($rows) => ['service' => $rows->first()->service, 'rows' => $rows])
                ->sortBy(fn ($group) => [$group['service']->sort_order, $group['service']->name]),
            'filter' => $filter,
            'search' => $search,
            'serviceId' => $request->integer('servicio') ?: null,
            'services' => Service::ordered()->get(),
            'counts' => [
                'todas' => Subscription::count(),
                'falta-correo' => Subscription::missingAccount()->count(),
                'vence-pronto' => Subscription::expiringSoon()->count(),
                'vencidas' => Subscription::expired()->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.subscriptions.form', $this->formData(new Subscription([
            'service_id' => $request->integer('servicio') ?: null,
            'starts_on' => today(),
            'expires_on' => today()->addMonthNoOverflow(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $subscription = Subscription::create($this->validated($request));

        return redirect()->route('admin.subscriptions.index')
            ->with('status', "Cuenta de {$subscription->service->name} para {$subscription->customer_name} agregada.");
    }

    public function edit(Subscription $subscription): View
    {
        return view('admin.subscriptions.form', $this->formData($subscription));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $subscription->update($this->validated($request));

        return redirect()->route('admin.subscriptions.index', ['servicio' => $subscription->service_id])
            ->with('status', "Cuenta de {$subscription->customer_name} actualizada.");
    }

    /** Extiende la vigencia un mes, desde el vencimiento (o desde hoy si ya venció). */
    public function renew(Subscription $subscription): RedirectResponse
    {
        $from = $subscription->expires_on->lt(today()) ? today() : $subscription->expires_on;
        $subscription->update(['expires_on' => $from->copy()->addMonthNoOverflow()]);

        return back()->with('status', "Cuenta de {$subscription->customer_name} renovada hasta el "
            .$subscription->expires_on->locale('es')->isoFormat('D [de] MMMM').'.');
    }

    /** Descarga todas las cuentas en un Excel con el formato "CUENTA DE <PLATAFORMA>". */
    public function export(SubscriptionsSpreadsheet $spreadsheet): StreamedResponse
    {
        $workbook = $spreadsheet->build(Subscription::with('service')->get());

        return response()->streamDownload(
            fn () => (new Xlsx($workbook))->save('php://output'),
            'cuentas-'.today()->toDateString().'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return back()->with('status', "Cuenta de {$subscription->customer_name} eliminada.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Subscription $subscription): array
    {
        // Correos ya usados por plataforma, para sugerirlos y autocompletar la clave.
        $accounts = Subscription::whereNotNull('account_email')->latest('updated_at')->get()
            ->groupBy('service_id')
            ->map(fn ($rows) => $rows->unique('account_email')->mapWithKeys(fn (Subscription $row) => [
                $row->account_email => $row->account_password,
            ]));

        return [
            'subscription' => $subscription,
            'services' => Service::ordered()->get(),
            'accounts' => $accounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'account_email' => ['nullable', 'string', 'max:255'],
            'account_password' => ['nullable', 'string', 'max:255'],
            'profile_name' => ['nullable', 'string', 'max:40'],
            'pin' => ['nullable', 'string', 'max:20'],
            'starts_on' => ['required', 'date'],
            'expires_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_phone.regex' => 'Escribe un número válido, p. ej. 300 123 4567.',
            'expires_on.after_or_equal' => 'El vencimiento no puede ser antes del inicio.',
        ]);

        $data['customer_phone'] = Phone::normalize($data['customer_phone'] ?? null);
        $data['account_email'] = filled($data['account_email'] ?? null) ? trim($data['account_email']) : null;

        return $data;
    }
}
