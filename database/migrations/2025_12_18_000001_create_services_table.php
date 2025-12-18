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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Duración estimada en minutos');
            $table->decimal('price', 15, 2);
            $table->integer('tax_rate')->default(10)->comment('0, 5, o 10 para IVA Paraguay');
            $table->decimal('commission_percentage', 5, 2)->nullable()->comment('% de comisión específico del servicio');
            $table->string('color', 7)->nullable()->comment('Color hex para UI (#RRGGBB)');
            $table->string('icon', 50)->nullable()->comment('Clase de icono Bootstrap');
            $table->integer('sort_order')->default(0)->comment('Orden para mostrar en POS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
