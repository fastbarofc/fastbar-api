<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2)->nullable();
            $table->integer('num_installments');
            $table->decimal('installment_amount', 10, 2);
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->integer('due_day');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payment_plans');
    }
};
