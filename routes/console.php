<?php

use Illuminate\Support\Facades\Schedule;

// Avisos de cuentas por vencer (necesita el cron de Laravel: "php artisan schedule:run" cada minuto).
// Cada vencimiento se avisa una sola vez, así que correrlo seguido no repite mensajes.
Schedule::command('cuentas:avisar')->hourly();
