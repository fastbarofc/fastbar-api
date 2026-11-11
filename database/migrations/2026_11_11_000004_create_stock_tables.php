<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchased_at');
            $table->string('supplier')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_purchase_id')->constrained('stock_purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('reason', 30);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('guests');
            $table->timestamp('sent_at');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('return_saved_at')->nullable();
            $table->foreignId('return_saved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('event_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_stock_id')->constrained('event_stocks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('expected_quantity', 12, 2)->nullable();
            $table->decimal('sent_quantity', 12, 2)->default(0);
            $table->decimal('returned_quantity', 12, 2)->nullable();
            $table->decimal('extra_quantity', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['event_stock_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->nullableMorphs('movementable');
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'type']);
        });

        Schema::create('stock_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->decimal('min_quantity', 12, 2)->nullable();
            $table->decimal('max_quantity', 12, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_limits');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('event_stock_items');
        Schema::dropIfExists('event_stocks');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_purchase_items');
        Schema::dropIfExists('stock_purchases');
    }
};
