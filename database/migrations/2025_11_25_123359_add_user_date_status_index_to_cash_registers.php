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
        Schema::table('cash_registers', function (Blueprint $table) {
            // Check if index already exists
            $indexName = 'cash_registers_user_id_register_date_status_index';
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->introspectTable('cash_registers');

            if (!$doctrineTable->hasIndex($indexName)) {
                $table->index(['user_id', 'register_date', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $indexName = 'cash_registers_user_id_register_date_status_index';
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->introspectTable('cash_registers');

            if ($doctrineTable->hasIndex($indexName)) {
                $table->dropIndex(['user_id', 'register_date', 'status']);
            }
        });
    }
};
