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
        Schema::table('kit_product', function (Blueprint $table) {
            $table->boolean('checklist')->default(false)->after('optional');
            $table->text('observation')->nullable()->after('checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kit_product', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'observation']);
        });
    }
};
