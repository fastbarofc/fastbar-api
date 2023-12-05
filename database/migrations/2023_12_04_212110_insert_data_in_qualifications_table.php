<?php

use App\Models\Qualification;
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
        Qualification::create(['description' => 'Agilidade']);
        Qualification::create(['description' => 'Comprometimento']);
        Qualification::create(['description' => 'Elegância']);
        Qualification::create(['description' => 'Pontualidade']);
        Qualification::create(['description' => 'Proatividade']);
        Qualification::create(['description' => 'Simpatia']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            //
        });
    }
};
