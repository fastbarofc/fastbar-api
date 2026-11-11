<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de documentos do evento (aba Documentos, igual ao Parthenon):
 * cada orçamento, contrato e aditivo gerado vira uma linha com o PDF guardado
 * e a situação (enviado, recusado, assinado, cancelado...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->unsignedInteger('number');
            $table->string('code', 40);
            $table->string('status', 30)->default('gerado');
            $table->decimal('total_value', 12, 2)->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('changes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['event_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_documents');
    }
};
