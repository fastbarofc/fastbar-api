<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_group_id')->constrained('product_groups')->cascadeOnDelete();
            $table->string('slug');
            $table->string('description');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_group_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subgroups');
    }
};
