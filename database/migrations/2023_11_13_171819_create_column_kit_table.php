<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->foreignId('kit_id')->after('id')->nullable()->cascadeOnDelete()->constrained(); //nao consegui resolver o erro de migration
        });
    }

    public function down(): void
    {
        Schema::dropColumn('kit_id');
    }
};
