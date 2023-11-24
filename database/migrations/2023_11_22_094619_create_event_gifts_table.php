<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('slug');
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_gifts');
    }
};
