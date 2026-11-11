<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planejamento Financeiro da mão de obra por evento (planilha do André, 2026-11-04):
 * linhas extras digitadas (Coordenações estimadas, Ajuda de custo...) e o "Custos: Sim/Não".
 * As linhas por setor vêm da Convocação/Quantificação e não ficam salvas aqui.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('event_labor_financial_plans')) {
            return;
        }
        Schema::create('event_labor_financial_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->json('extra_lines')->nullable();
            $table->boolean('costs_confirmed')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_labor_financial_plans');
    }
};
