<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un servicio es la plataforma que se vende (Netflix, Spotify, ChatGPT...).
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 110)->unique();
            $table->string('tagline', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('color', 7)->default('#4d8dff');
            $table->json('features')->nullable();
            $table->boolean('is_available')->default(true);  // hay cupos / stock
            $table->boolean('is_active')->default(true);     // visible en la tienda
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
