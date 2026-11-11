<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class FinancialOperation extends Model
{
    use HasFactory, Sortable, Filterable, SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'account',
        'nature',
        'type',
        'dre_section',
        'dre_order',
        'active',
    ];

    protected $casts = [
        'account' => 'string',
        'nature' => 'string',
        'type' => 'string',
        'dre_order' => 'integer',
        'active' => 'boolean',
    ];

    protected static $sortableFields = [
        'id',
        'code',
        'description',
        'account',
        'nature',
        'type',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'code',
        'description',
        'account',
        'nature',
        'type',
        'dre_section',
        'active',
    ];

    protected static $searchableFields = [
        'code',
        'description',
    ];

    /** Grupos do DRE/ARM, na ordem do demonstrativo (sinal e subtotal que fecha o grupo). */
    public const DRE_SECTIONS = [
        'receita_total' => ['label' => 'Receita total', 'sign' => 1, 'subtotal' => null],
        'deducoes' => ['label' => 'Deduções da receita', 'sign' => -1, 'subtotal' => 'Receita líquida'],
        'custos_variaveis' => ['label' => 'Custos variáveis', 'sign' => -1, 'subtotal' => 'Lucro bruto'],
        'despesas_operacionais' => ['label' => 'Despesas operacionais', 'sign' => -1, 'subtotal' => 'Lucro operacional (EBITDA)'],
        'despesas_nao_operacionais' => ['label' => 'Despesas não operacionais', 'sign' => -1, 'subtotal' => null],
        'receitas_nao_operacionais' => ['label' => 'Receitas não operacionais', 'sign' => 1, 'subtotal' => 'Lucro líquido'],
        'retirada_socios' => ['label' => 'Retirada de sócios', 'sign' => -1, 'subtotal' => 'Lucro retido'],
    ];

    /** Grupo do DRE: o salvo na estrutura; sem ele, receita = Receita total, despesa variável = Custos variáveis, fixa = Despesas operacionais. */
    public function getEffectiveDreSectionAttribute(): ?string
    {
        if ($this->dre_section === 'fora') {
            return null;
        }
        if ($this->dre_section && isset(self::DRE_SECTIONS[$this->dre_section])) {
            return $this->dre_section;
        }
        if ($this->account === 'synthetic') {
            return null;
        }
        if ($this->nature === 'revenue') {
            return 'receita_total';
        }
        return $this->type === 'variable' ? 'custos_variaveis' : 'despesas_operacionais';
    }

    /** Plano padrão da Fast Bar (ARM): código, descrição, natureza, tipo, grupo do DRE. */
    public const DEFAULT_PLAN = [
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

    public function payables()
    {
        return $this->hasMany(Payable::class);
    }
}
