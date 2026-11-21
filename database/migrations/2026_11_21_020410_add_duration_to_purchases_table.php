<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuántos meses dura la cuenta comprada y cuándo vence (fecha de compra + meses).
     * Vacíos en los "otros gastos", que no vencen.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')->nullable()->after('purchased_on');
            $table->date('expires_on')->nullable()->after('duration_months');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'expires_on']);
        });
    }
};
