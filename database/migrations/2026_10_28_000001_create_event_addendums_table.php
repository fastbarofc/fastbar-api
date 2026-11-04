<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_addendums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->json('types');
            $table->json('document');
            $table->decimal('previous_total', 12, 2)->nullable();
            $table->decimal('new_total', 12, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'number']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->decimal('addendum_value', 12, 2)->default(0)->after('total_value');
        });

        Schema::table('event_histories', function (Blueprint $table) {
            $table->foreignId('event_addendum_id')->nullable()->after('user_id')->constrained('event_addendums')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_addendum_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('addendum_value');
        });

        Schema::dropIfExists('event_addendums');
    }
};
