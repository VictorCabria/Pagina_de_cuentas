<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas del pedido. Guardan nombre y precio del momento de la compra,
     * así la orden no cambia si luego se edita o elimina el plan.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name', 100);
            $table->string('plan_name', 100);
            $table->unsignedSmallInteger('duration_months');
            $table->unsignedTinyInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('original_price', 12, 2);
            $table->unsignedTinyInteger('discount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
