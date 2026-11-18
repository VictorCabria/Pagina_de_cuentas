<?php

namespace App\Console\Commands;

use App\Support\ExpiryAlerts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuentas:avisar')]
#[Description('Avisa en el panel y por WhatsApp las cuentas de clientes que están por vencer')]
class SendExpiryAlerts extends Command
{
    public function handle(ExpiryAlerts $alerts): int
    {
        $sent = $alerts->run();

        $this->info($sent->isEmpty()
            ? 'No hay cuentas nuevas por vencer.'
            : ($sent->count() === 1 ? 'Avisada 1 cuenta por vencer.' : "Avisadas {$sent->count()} cuentas por vencer."));

        return self::SUCCESS;
    }
}
