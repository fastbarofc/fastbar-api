<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planejamento > Mão de Obra (planilha "Quantificação de Mão de Obra por Evento" do André, 2026-11-04):
 * - labor_sectors: os setores da planilha (Mesa, Garçons/Maitres/Banheirista, Portaria...), com cor e
 *   o nome da linha correspondente na Tabela de valores (para a diária do Relatório Financeiro).
 * - event_labor_quantifications: quantidade + observação por evento × setor (Copa tem também transporte).
 * - event_labor_convocations: quem foi convocado em cada setor do evento.
 * - event_labor_payments: valor por colaborador convocado (diária + extras), marcar pago, PDF para assinar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pode rodar em banco onde uma versão anterior criou event_labor_payments (por função) ou parou no meio:
        // cria só o que falta, refaz event_labor_payments no formato por setor e semeia os setores se vazio.
        if (Schema::hasTable('event_labor_payments') && Schema::hasColumn('event_labor_payments', 'office_event_id')) {
            Schema::drop('event_labor_payments');
        }

        if (!Schema::hasTable('labor_sectors')) Schema::create('labor_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->string('value_table_role')->nullable()->comment('Nome da linha na Tabela de valores');
            $table->boolean('has_transport')->default(false)->comment('Coluna "Qtd. Transporte" (Copa)');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        if (!Schema::hasTable('event_labor_quantifications')) Schema::create('event_labor_quantifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('labor_sector_id')->constrained('labor_sectors')->onDelete('cascade');
            $table->decimal('quantity', 8, 1)->default(0);
            $table->decimal('transport_quantity', 8, 1)->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['event_id', 'labor_sector_id']);
        });

        if (!Schema::hasTable('event_labor_convocations')) Schema::create('event_labor_convocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('labor_sector_id')->constrained('labor_sectors')->onDelete('cascade');
            $table->foreignId('collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['event_id', 'labor_sector_id', 'collaborator_id'], 'event_labor_convocations_unique');
        });

        if (!Schema::hasTable('event_labor_payments')) Schema::create('event_labor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->foreignId('labor_sector_id')->nullable()->constrained('labor_sectors')->onDelete('set null');
            $table->decimal('base_value', 10, 2)->default(0);
            $table->decimal('extra_hours_value', 10, 2)->default(0);
            $table->decimal('after_party_value', 10, 2)->default(0);
            $table->decimal('coordination_value', 10, 2)->default(0);
            $table->decimal('other_value', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['event_id', 'collaborator_id', 'labor_sector_id'], 'event_labor_payments_unique');
        });

        $sectors = [
            ['Mesa', '#C55A11', 'GARÇOM', false],
            ['Garçons / Maitres / Banheirista', '#2E5395', 'GARÇOM', false],
            ['Portaria', '#C026A8', 'GARÇOM', false],
            ['Limpeza', '#548235', 'LIMPEZA', false],
            ['Copa / Churrasqueiro', '#7F6000', 'GARÇOM', true],
            ['Cozinha', '#833C0C', 'COZINHA', false],
            ['Financeiro', '#7030A0', 'GARÇOM', false],
            ['Fastbar', '#274E13', 'GARÇOM', false],
            ['Gestor de Experiência', '#148F77', 'GARÇOM', false],
            ['Pizzaria', '#4C1130', 'GARÇOM', false],
            ['Fiscal', '#C00000', 'GARÇOM', false],
            ['Coordenação Geral', '#1F4E79', 'GARÇOM', false],
        ];
        if (DB::table('labor_sectors')->count() > 0) {
            return;
        }
        foreach ($sectors as $i => [$name, $color, $role, $transport]) {
            DB::table('labor_sectors')->insert([
                'name' => $name, 'color' => $color, 'value_table_role' => $role, 'has_transport' => $transport,
                'position' => $i + 1, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_labor_payments');
        Schema::dropIfExists('event_labor_convocations');
        Schema::dropIfExists('event_labor_quantifications');
        Schema::dropIfExists('labor_sectors');
    }
};
