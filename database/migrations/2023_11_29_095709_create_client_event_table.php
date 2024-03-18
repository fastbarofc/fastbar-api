<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->foreignId('client_id')->cascadeOnDelete()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_event');
    }
};
