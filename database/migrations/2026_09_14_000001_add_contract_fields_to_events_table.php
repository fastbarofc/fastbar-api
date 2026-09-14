<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('glass_cup_hours')->nullable()->after('duration_after');
            $table->json('contract_print_data')->nullable()->after('contract_body');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['glass_cup_hours', 'contract_print_data']);
        });
    }
};
