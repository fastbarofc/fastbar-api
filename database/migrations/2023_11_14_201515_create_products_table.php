<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('file_id')->nullable()->constrained();
            $table->string('barcode')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained();
            $table->foreignId('product_group_id')->nullable()->constrained();
            $table->float('weight')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('value', 9, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->boolean('marketable')->nullable();
            $table->float('average_consumption')->nullable();
            $table->boolean('display_checklist')->nullable();
            $table->boolean('own_production')->nullable();
            $table->float('recipe_yield')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
