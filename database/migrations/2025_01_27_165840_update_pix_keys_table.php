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
        Schema::table('pix_keys', function (Blueprint $table) {
            $table->nullableMorphs('pix_keyable');
        });

        DB::statement('
             UPDATE pix_keys
             SET pix_keyable_type = "App\\\\Models\\\\Collaborator",
                 pix_keyable_id = collaborator_id
             WHERE collaborator_id IS NOT NULL'
        );

        Schema::table('pix_keys', function (Blueprint $table) {
            $table->dropForeign(['collaborator_id']);
            $table->dropColumn('collaborator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pix_keys', function (Blueprint $table) {
           
            $table->foreignId('collaborator_id')->nullable();

            DB::statement('
                UPDATE pix_keys 
                SET collaborator_id = pix_keyable_id 
                WHERE pix_keyable_type = "App\\\\Models\\\\Collaborator"
            ');

            $table->foreign('collaborator_id')->references('id')->on('collaborators');

            $table->dropMorphs('pix_keyable');
        });
    }
};
