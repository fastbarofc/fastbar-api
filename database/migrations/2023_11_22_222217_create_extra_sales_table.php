<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_sales', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('slug');
            $table->foreignId('unit_id')->nullable()->cascadeOnDelete()->constrained();
            $table->integer('quantity');
            $table->string('value_unit');
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_sales');
    }
};
