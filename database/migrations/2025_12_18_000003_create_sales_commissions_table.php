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
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Vendedor');
            $table->enum('item_type', ['product', 'service']);
            $table->unsignedBigInteger('item_id')->comment('ID del producto o servicio');
            $table->string('item_name')->comment('Snapshot del nombre');
            $table->decimal('quantity', 10, 2);
            $table->decimal('sale_amount', 15, 2)->comment('Monto total vendido de este item');
            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('commission_amount', 15, 2)->comment('Monto calculado de comisión');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable()->comment('Referencia de pago');
            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_commissions');
    }
};
