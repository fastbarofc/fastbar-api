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
        Schema::table('bank_details', function (Blueprint $table) {
            $table->nullableMorphs('bank_detailable');
        });

        DB::statement('
            UPDATE bank_details 
            SET bank_detailable_type = "App\\\\Models\\\\Collaborator",
                bank_detailable_id = collaborator_id
            WHERE collaborator_id IS NOT NULL
        ');

        Schema::table('bank_details', function (Blueprint $table) {
            $table->dropForeign(['collaborator_id']);
            $table->dropColumn('collaborator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_details', function (Blueprint $table) {
           
            $table->foreignId('collaborator_id')->nullable();

            DB::statement('
                UPDATE bank_details 
                SET collaborator_id = bank_detailable_id 
                WHERE bank_detailable_type = "App\\\\Models\\\\Collaborator"
            ');

            $table->foreign('collaborator_id')->references('id')->on('collaborators');

            $table->dropMorphs('bank_detailable');
        });
    }
};
