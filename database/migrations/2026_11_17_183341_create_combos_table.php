<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Combos: varias plataformas juntas a un precio especial.
     * price_cop vacío = se calcula con la tasa USD→COP, igual que los planes.
     */
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 110)->unique();
            $table->string('description', 255)->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('duration_months')->default(1);
            $table->decimal('price_usd', 10, 2);
            $table->unsignedInteger('price_cop')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
