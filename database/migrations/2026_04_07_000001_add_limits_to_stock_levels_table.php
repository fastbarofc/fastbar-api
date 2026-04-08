<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->integer('min_quantity')->nullable()->after('current_quantity');
            $table->integer('max_quantity')->nullable()->after('min_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropColumn(['min_quantity', 'max_quantity']);
        });
    }
};
