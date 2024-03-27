<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->constrained();
            $table->foreignId('invoice_client_id')->nullable()->constrained('clients');
            $table->foreignId('collaborator_id')->constrained();
            $table->string('slug');
            $table->foreignId('event_type_id')->constrained();
            $table->foreignId('event_place_id')->constrained();
            $table->foreignId('event_status_id')->constrained();
            $table->foreignId('partner_id')->nullable()->constrained();
            $table->foreignId('decorator_id')->nullable()->constrained();
            $table->foreignId('event_capture_id')->nullable()->constrained();
            $table->foreignId('ceremonial_id')->nullable()->constrained();
            $table->decimal('lease');
            $table->integer('guests');
            $table->integer('min_guests');
            $table->dateTime('date_time');
            $table->integer('duration');
            $table->integer('duration_after')->default(0);
            $table->decimal('unit_value');
            $table->decimal('discount')->nullable();
            $table->decimal('unit_discount')->nullable();
            $table->decimal('unit_insurance')->nullable();
            $table->decimal('buffet_value')->nullable();
            $table->decimal('total_value')->nullable();
            $table->longText('contract_body')->nullable();
            $table->string('parthenon_courtesies')->nullable();
            $table->string('partner_courtesies')->nullable();
            $table->string('courtesies')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
