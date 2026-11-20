<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compras de cuentas a proveedores: lo que se invierte para luego vender.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 120)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->date('purchased_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchased_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
