<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plano de contas com a estrutura do DRE/ARM (como no sistema da MAV):
 * cada conta tem o grupo do DRE (dre_section), a posição dentro do grupo (dre_order) e se está ativa.
 * O plano de contas do Fastbar é cadastrado à parte (ARM).
 * Receitas passam a ter conta do plano (opcional; sem conta = "Receita de eventos").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_operations', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_operations', 'dre_section')) {
                $table->string('dre_section', 40)->nullable()->after('type');
            }
            if (!Schema::hasColumn('financial_operations', 'dre_order')) {
                $table->integer('dre_order')->nullable()->after('dre_section');
            }
            if (!Schema::hasColumn('financial_operations', 'active')) {
                $table->boolean('active')->default(true)->after('dre_order');
            }
        });

        if (!Schema::hasColumn('receivables', 'financial_operation_id')) {
            Schema::table('receivables', function (Blueprint $table) {
                $table->foreignId('financial_operation_id')->nullable()->after('payment_method_id')
                    ->constrained('financial_operations')->nullOnDelete();
            });
        }

        // Receita avulsa (lançada pela conciliação bancária) não tem evento nem forma de pagamento.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE receivables MODIFY receivableable_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE receivables MODIFY receivableable_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE receivables MODIFY payment_method_id BIGINT UNSIGNED NULL');
        }

        // No Fastbar o plano de contas vem do ARM (tarefa de Relatórios/DRE); aqui não grava plano padrão.
    }

    public function down(): void
    {
        if (Schema::hasColumn('receivables', 'financial_operation_id')) {
            Schema::table('receivables', function (Blueprint $table) {
                $table->dropConstrainedForeignId('financial_operation_id');
            });
        }
        Schema::table('financial_operations', function (Blueprint $table) {
            $table->dropColumn(['dre_section', 'dre_order', 'active']);
        });
    }

    /** Plano padrão de buffet: código, descrição, natureza, tipo, grupo do DRE. */
    private const DEFAULT_PLAN = [
        ['3.01.001', 'Receita de eventos (buffet)', 'revenue', 'variable', 'receita_total'],
        ['3.01.002', 'Receita de locação', 'revenue', 'variable', 'receita_total'],
        ['3.01.003', 'Receita de aditivos e excedentes', 'revenue', 'variable', 'receita_total'],
        ['3.02.001', 'Impostos sobre faturamento', 'expense', 'variable', 'deducoes'],
        ['4.01.001', 'Terceirizados', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.002', 'Mão de obra do evento', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.003', 'Insumos e alimentos', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.004', 'Bebidas', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.005', 'Hotel', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.006', 'Lanche da equipe', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.007', 'BV de parceiros', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.008', 'Transporte e frete', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.009', 'Locação de materiais', 'expense', 'variable', 'custos_variaveis'],
        ['5.01.001', 'Salários e encargos', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.002', 'Aluguel', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.003', 'Energia, água e gás', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.004', 'Contabilidade', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.005', 'Telefone e internet', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.006', 'Marketing e publicidade', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.007', 'Manutenção', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.008', 'Sistemas e assinaturas', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.02.001', 'Juros e tarifas bancárias', 'expense', 'fixed', 'despesas_operacionais'],
        ['6.01.001', 'Investimentos', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['6.01.002', 'Empréstimos e financiamentos', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['7.01.001', 'Receitas financeiras', 'revenue', 'fixed', 'receitas_nao_operacionais'],
        ['7.01.002', 'Outras receitas', 'revenue', 'variable', 'receitas_nao_operacionais'],
        ['8.01.001', 'Retirada dos sócios', 'expense', 'fixed', 'retirada_socios'],
    ];
};
