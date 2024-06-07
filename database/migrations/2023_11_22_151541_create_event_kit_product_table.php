<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_kit_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_kit_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('value')->nullable();
            $table->integer('ordination')->nullable();
            $table->boolean('optional')->default(false)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('average consumption',9 ,2)->nullable();
            $table->integer('quantity')->nullable();
            $table->longText('comments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_kit_product');
    }
};
