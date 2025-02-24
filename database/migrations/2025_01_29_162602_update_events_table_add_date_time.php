<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('date')->after('lease')->nullable();
            $table->time('time')->after('date')->nullable();
        });

        DB::statement("UPDATE events SET
            date = DATE(date_time),
            time = TIME(date_time)
        WHERE date_time IS NOT NULL");

        Schema::table('events', function (Blueprint $table) {
             $table->dropColumn('date_time');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('date_time')->nullable();
        });

        DB::statement("UPDATE events SET 
        date_time = CONCAT(date, ' ', COALESCE(time, '00:00:00'))
        WHERE date IS NOT NULL");

        Schema::table('events', function (Blueprint $table) {
             $table->dropColumn(['date', 'time']);
        });

    }
};
