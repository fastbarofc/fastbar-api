<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_events', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('slug');
            $table->boolean('daily');
            $table->boolean('coordinator');
            $table->boolean('date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_events');
    }
};
