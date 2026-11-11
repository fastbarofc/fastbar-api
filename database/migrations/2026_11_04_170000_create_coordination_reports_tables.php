<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planejamento > Relatório Coordenação (André, 2026-11-04): o coordenador preenche formulários do Google Forms
     * para cada evento; o relatório gerado fica no Google Drive. Aqui ficam os tipos de relatório (com o link do formulário)
     * e, para cada evento, o link do arquivo no Drive.
     */
    public function up(): void
    {
        Schema::create('coordination_report_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('form_url', 1000)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('coordination_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('coordination_report_type_id')->constrained('coordination_report_types')->onDelete('cascade');
            $table->string('drive_url', 1000);
            $table->timestamp('received_at')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['event_id', 'coordination_report_type_id'], 'coord_reports_event_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordination_reports');
        Schema::dropIfExists('coordination_report_types');
    }
};
