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
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('lease', 10, 2)->nullable()->change();
            $table->decimal('buffet_value', 10, 2)->nullable()->change();
            $table->decimal('total_value', 10, 2)->nullable()->change();
            $table->decimal('sub_total', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('lease')->nullable()->change();
            $table->decimal('buffet_value')->nullable()->change();
            $table->decimal('total_value')->nullable()->change();
            $table->decimal('sub_total')->nullable()->change();
        });
    }
};
