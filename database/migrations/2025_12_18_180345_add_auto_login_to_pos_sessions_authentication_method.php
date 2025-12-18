<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para agregar 'auto-login'
        DB::statement("ALTER TABLE pos_sessions MODIFY COLUMN authentication_method ENUM('pin', 'rfid', 'pin+rfid', 'auto-login') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM original
        DB::statement("ALTER TABLE pos_sessions MODIFY COLUMN authentication_method ENUM('pin', 'rfid', 'pin+rfid') NOT NULL");
    }
};
