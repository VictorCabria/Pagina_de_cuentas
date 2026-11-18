<?php

namespace App\Http\Controllers;

use App\Support\ExpiryAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronController extends Controller
{
    /**
     * Revisa las cuentas por vencer. Para hostings sin cron propio: un servicio
     * externo llama a esta URL con el secreto de CRON_SECRET.
     */
    public function __invoke(Request $request, ExpiryAlerts $alerts): JsonResponse
    {
        $secret = (string) config('services.cron.secret');
        $given = (string) ($request->bearerToken() ?? $request->query('token'));

        abort_if($secret === '' || ! hash_equals($secret, $given), 404);

        return response()->json(['alerted' => $alerts->run()->count()]);
    }
}
