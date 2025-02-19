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
        Schema::table('event_places', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
            $table->string('responsible')->nullable()->change();
            $table->string('description')->nullable()->change();
            $table->string('phone_1')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_places', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change(); 
            $table->string('responsible')->nullable(false)->change();
            $table->string('description')->nullable(false)->change();
            $table->string('phone_1')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
