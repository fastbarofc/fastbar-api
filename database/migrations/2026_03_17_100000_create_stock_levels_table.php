<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->integer('current_quantity')->default(0);
            $table->decimal('weighted_average_price', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'department_id']);
            $table->index('department_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
