<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vencimiento por el que ya se avisó. Si la cuenta se renueva (cambia
     * expires_on), se vuelve a avisar cuando se acerque el nuevo vencimiento.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('expiry_alerted_for')->nullable()->after('expires_on');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('expiry_alerted_for');
        });
    }
};
