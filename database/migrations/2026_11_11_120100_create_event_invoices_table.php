<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notas fiscais de serviço (NFS-e) emitidas pela Focus NFe para os eventos.
 * "ref" é a referência única que a Focus usa para consultar e cancelar a nota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('ref', 60)->unique();
            $table->string('environment', 20); // homologacao, producao
            $table->string('status', 40)->default('processando_autorizacao'); // processando_autorizacao, autorizado, erro_autorizacao, cancelado
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->string('number', 30)->nullable();
            $table->string('verification_code', 60)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->string('xml_url', 500)->nullable();
            $table->text('message')->nullable();
            $table->json('last_response')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invoices');
    }
};
