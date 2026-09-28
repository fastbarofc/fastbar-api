<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('responsible')->nullable()->change();
            $table->string('email')->nullable()->change();
        });

        Schema::table('decorators', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        Schema::table('ceremonials', function (Blueprint $table) {
            $table->string('responsible')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('responsible')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('decorators', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('ceremonials', function (Blueprint $table) {
            $table->string('responsible')->nullable(false)->change();
        });
    }
};
