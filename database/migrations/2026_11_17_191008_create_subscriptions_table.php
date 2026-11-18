<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas entregadas a clientes (lo que antes se llevaba en el Excel):
     * quién tiene qué plataforma, con qué correo/clave/PIN y hasta cuándo.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 80);
            $table->string('customer_phone', 20)->nullable();
            $table->string('account_email')->nullable();
            $table->text('account_password')->nullable();   // cifrada
            $table->string('pin', 20)->nullable();
            $table->date('starts_on');
            $table->date('expires_on')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
