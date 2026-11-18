<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedidos hechos en la tienda y enviados por WhatsApp (sin pago en línea).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('token')->unique();            // URL pública de la confirmación, no adivinable
            $table->string('customer_name', 80);
            $table->string('customer_phone', 20);
            $table->string('currency', 3);
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();          // notas internas del admin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
