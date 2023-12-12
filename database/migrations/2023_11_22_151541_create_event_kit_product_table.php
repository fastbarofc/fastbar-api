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
            $table->foreignId('event_kit_id')->cascadeOnDelete()->constrained();
            $table->foreignId('product_id')->cascadeOnDelete()->constrained();
            $table->foreignId('event_id')->cascadeOnDelete()->constrained();
            $table->decimal('value');
            $table->integer('ordination');
            $table->boolean('optional')->default(false);
            $table->string('unit');
            $table->decimal('average consumption',9 ,2);
            $table->integer('quantity');
            $table->longText('comments');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_kit_product');
    }
};
