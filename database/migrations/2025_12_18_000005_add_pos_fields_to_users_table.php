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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pos_pin')->nullable()->after('password')->comment('PIN hasheado para POS');
            $table->string('rfid_code', 100)->unique()->nullable()->after('pos_pin')->comment('Código RFID único');
            $table->boolean('pos_enabled')->default(false)->after('rfid_code')->comment('Puede usar POS');
            $table->boolean('pos_require_rfid')->default(false)->after('pos_enabled')->comment('Requiere RFID + PIN (2FA)');
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('pos_require_rfid')->comment('% comisión por defecto');
        });

        // Índice para RFID
        Schema::table('users', function (Blueprint $table) {
            $table->index('rfid_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rfid_code']);
            $table->dropColumn([
                'pos_pin',
                'rfid_code',
                'pos_enabled',
                'pos_require_rfid',
                'commission_percentage'
            ]);
        });
    }
};
