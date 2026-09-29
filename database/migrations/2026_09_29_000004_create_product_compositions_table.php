<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['product_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_compositions');
    }
};
