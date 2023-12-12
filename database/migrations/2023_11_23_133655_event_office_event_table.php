<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_office_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->foreignId('office_event_id')->cascadeOnDelete()->constrained();
            $table->decimal('quantity');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_office_event');
    }
};
