<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->foreignId('material_id')->cascadeOnDelete()->constrained();
            $table->boolean('shown_in_checklist')->default(false);
            $table->boolean('selected')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_material');
    }
};
