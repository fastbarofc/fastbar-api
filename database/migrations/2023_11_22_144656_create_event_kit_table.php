<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_kit_id')->nullable()->cascadeOnDelete()->constrained();
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->string('name');
            $table->decimal('value')->nullable();
            $table->string('themed_table')->default(false);
            $table->integer('ordination')->nullable();
            $table->string('observation')->nullable();
            $table->boolean('optional')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_kit');
    }
};
