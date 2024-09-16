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
            $table->string('name')->nullable();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->foreignId('invoice_client_id')->nullable()->constrained('clients');
            $table->foreignId('event_id')->nullable()->constrained();
            $table->foreignId('collaborator_id')->nullable()->constrained();
            $table->foreignId('event_type_id')->nullable()->constrained();
            $table->foreignId('event_place_id')->nullable()->constrained();
            $table->foreignId('event_status_id')->nullable()->constrained();
            $table->foreignId('partner_id')->nullable()->constrained();
            $table->foreignId('decorator_id')->nullable()->constrained();
            $table->foreignId('event_capture_id')->nullable()->constrained();
            $table->foreignId('ceremonial_id')->nullable()->constrained();
            $table->decimal('lease')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('duration_after')->nullable()->default(0);
            $table->decimal('unit_insurance')->nullable();
            $table->decimal('total_value')->nullable();
            $table->longText('contract_body')->nullable();
            $table->string('parthenon_courtesies')->nullable();
            $table->string('partner_courtesies')->nullable();
            $table->boolean('is_draft')->default(true)->nullable();
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
