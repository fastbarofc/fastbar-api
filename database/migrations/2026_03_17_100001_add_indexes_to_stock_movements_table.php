<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'type', 'deleted_at']);
            $table->index(['department_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'type', 'deleted_at']);
            $table->dropIndex(['department_id', 'product_id']);
        });
    }
};
