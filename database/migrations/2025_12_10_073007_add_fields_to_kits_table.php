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
        Schema::table('kits', function (Blueprint $table) {
            $table->boolean('themed_table')->default(false)->after('name');
            $table->boolean('optional')->default(false)->after('themed_table');
            $table->boolean('checklist')->default(false)->after('optional');
            $table->string('observation')->nullable()->after('checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->dropColumn(['themed_table', 'optional', 'checklist', 'observation']);
        });
    }
};
