<?php

namespace App\Http\Middleware;

use App\Support\ExpiryAlerts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Respaldo para hostings sin cron: al usar el panel se revisan (como mucho
 * una vez por hora) las cuentas por vencer.
 */
class CheckExpiryAlerts
{
    public function __construct(private ExpiryAlerts $alerts) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $this->alerts->runIfDue();
        }

        return $next($request);
    }
}
