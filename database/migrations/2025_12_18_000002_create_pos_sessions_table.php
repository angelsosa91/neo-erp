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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_token', 64)->unique();
            $table->enum('authentication_method', ['pin', 'rfid', 'pin+rfid', 'auto-login']);
            $table->string('rfid_code', 100)->nullable()->comment('Código RFID usado en esta sesión');
            $table->string('terminal_identifier', 100)->nullable()->comment('ID del dispositivo/navegador');
            $table->timestamp('opened_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['active', 'expired', 'closed'])->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('session_token');
            $table->index(['user_id', 'status', 'opened_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
