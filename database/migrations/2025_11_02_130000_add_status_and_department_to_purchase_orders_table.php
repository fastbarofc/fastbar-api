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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'received', 'cancelled'])
                ->default('pending')
                ->after('cost_center_id');
            $table->foreignId('department_id')
                ->nullable()
                ->after('cost_center_id')
                ->constrained()
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};

