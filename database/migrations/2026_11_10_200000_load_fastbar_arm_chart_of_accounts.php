<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Plano de contas da Fast Bar conforme o ARM (3º trimestre 2026).
 * Grava só as contas (códigos) que ainda não existem; o grupo do DRE pode ser trocado depois no plano de contas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('financial_operations')->whereNull('deleted_at')->pluck('code')->flip();
        $now = now();
        $order = [];
        foreach (self::PLAN as [$code, $description, $nature, $type, $section]) {
            $order[$section] = ($order[$section] ?? 0) + 1;
            if (isset($existing[$code])) {
                continue;
            }
            DB::table('financial_operations')->insert([
                'code' => $code,
                'description' => $description,
                'account' => 'analytic',
                'nature' => $nature,
                'type' => $type,
                'dre_section' => $section,
                'dre_order' => $order[$section],
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $codes = array_column(self::PLAN, 0);
        DB::table('financial_operations')
            ->whereIn('code', $codes)
            ->whereNotExists(fn ($q) => $q->from('payables')->whereColumn('payables.financial_operation_id', 'financial_operations.id'))
            ->whereNotExists(fn ($q) => $q->from('receivables')->whereColumn('receivables.financial_operation_id', 'financial_operations.id'))
            ->delete();
    }

    /** Plano padrão da Fast Bar (ARM): código, descrição, natureza, tipo, grupo do DRE. */
    private const PLAN = [
        ['3.01.001', 'Vendas', 'revenue', 'variable', 'receita_total'],
        ['3.01.002', 'Recebimentos', 'revenue', 'variable', 'receita_total'],
        ['3.01.003', 'Rendimentos', 'revenue', 'variable', 'receita_total'],
        ['3.01.004', 'Ajuste de saldo', 'revenue', 'variable', 'receita_total'],
        ['4.01.001', 'Mercadorias', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.002', 'Frete', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.003', 'Impostos sobre vendas', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.004', 'ICMS', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.005', 'Freelancer', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.006', 'Comissão', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.007', 'Desp. com locação', 'expense', 'variable', 'custos_variaveis'],
        ['4.01.008', 'Desp. com eventos', 'expense', 'variable', 'custos_variaveis'],
        ['5.01.001', 'Custo de ocupação', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.002', 'Serviços mensais', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.003', 'Salários, benefícios e encargos', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.004', 'Financeiras', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.005', 'Diversos', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.006', 'Manutenções', 'expense', 'fixed', 'despesas_operacionais'],
        ['5.01.007', 'Veículos', 'expense', 'fixed', 'despesas_operacionais'],
        ['6.01.001', 'Investimentos', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['6.01.002', 'Consórcio', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['6.01.003', 'Empréstimos', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['6.01.004', 'Publicidade', 'expense', 'fixed', 'despesas_nao_operacionais'],
        ['7.01.001', 'Outras vendas', 'revenue', 'variable', 'receitas_nao_operacionais'],
        ['8.01.001', 'Distribuição de lucros', 'expense', 'fixed', 'retirada_socios'],
    ];
};
