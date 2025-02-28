<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EventStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_statuses', function (Blueprint $table) {
            EventStatus::create(['name' => 'Contrato']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_statuses', function (Blueprint $table) {
            EventStatus::where('name', 'Contrato')->delete();
        });
    }
};
