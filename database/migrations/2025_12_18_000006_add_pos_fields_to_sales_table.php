<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('pos_session_id')->nullable()->after('user_id')
                ->constrained('pos_sessions')->onDelete('set null')
                ->comment('Sesión POS que creó esta venta');
            $table->decimal('tip_amount', 15, 2)->default(0)->after('total')
                ->comment('Propina');
        });

        // Índice para consultas por sesión POS
        Schema::table('sales', function (Blueprint $table) {
            $table->index('pos_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['pos_session_id']);
            $table->dropIndex(['pos_session_id']);
            $table->dropColumn(['pos_session_id', 'tip_amount']);
        });
    }
};
