<?php

namespace App\Providers;

use App\Support\Pricing;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una instancia por petición: las promociones vigentes se consultan una sola vez.
        $this->app->scoped(Pricing::class);
    }

    public function boot(): void
    {
        //
    }
}
