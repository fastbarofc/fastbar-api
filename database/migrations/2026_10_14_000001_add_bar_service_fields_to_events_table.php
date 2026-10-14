<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('guests_per_bartender')->nullable()->after('glass_cup_hours');
            $table->string('bar_counter_type', 20)->nullable()->after('guests_per_bartender');
            $table->string('cup_type', 20)->nullable()->after('bar_counter_type');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['guests_per_bartender', 'bar_counter_type', 'cup_type']);
        });
    }
};
