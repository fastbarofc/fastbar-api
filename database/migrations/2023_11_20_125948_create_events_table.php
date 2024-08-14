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
            $table->foreignId('event_id')->nullable()->constrained();
            $table->foreignId('collaborator_id')->constrained();
            $table->foreignId('event_type_id')->constrained();
            $table->foreignId('event_place_id')->nullable()->constrained();
            $table->foreignId('event_status_id')->constrained();
            $table->foreignId('partner_id')->constrained();
            $table->foreignId('decorator_id')->constrained();
            $table->foreignId('event_capture_id')->constrained();
            $table->foreignId('ceremonial_id')->constrained();
            $table->decimal('lease')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('duration_after')->default(0);
            $table->decimal('unit_insurance')->nullable();
            $table->longText('contract_body')->nullable();
            $table->string('parthenon_courtesies')->nullable();
            $table->string('partner_courtesies')->nullable();
            $table->foreignId('competing_company_id')->nullable()->constrained();
            $table->foreignId('lost_reason_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
