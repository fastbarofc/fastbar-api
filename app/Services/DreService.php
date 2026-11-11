<?php

namespace App\Services;

use App\Models\Event;
use App\Models\FinancialOperation;
use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Support\Collection;

/**
 * DRE gerencial e ARM mensal (como no sistema da MAV), a partir das contas a receber e a pagar.
 *
 * Regimes:
 * - competencia: data do evento (sem evento, o vencimento);
 * - vencimento: data de vencimento;
 * - caixa: data do pagamento/recebimento (só o que foi baixado, pelo valor baixado).
 *
 * Cada lançamento cai na conta do plano de contas; sem conta, usa a conta padrão da categoria
 * (terceirizado, mão de obra, hotel, lanche, BV, compra) ou "Receita de eventos" para recebimentos.
 * A estrutura (grupo e ordem das contas) vem de Configurações > Plano de contas.
 */
class DreService
{
    /** Conta padrão (código do ARM da Fast Bar) para contas a pagar sem conta do plano, pela categoria. */
    public const CATEGORY_ACCOUNT = [
        'terceirizado' => '4.01.005', // Freelancer
        'mao_de_obra' => '4.01.005',  // Freelancer
        'compra' => '4.01.001',       // Mercadorias
        'hotel' => '4.01.008',        // Desp. com eventos
        'lanche' => '4.01.008',       // Desp. com eventos
        'bv' => '4.01.006',           // Comissão
    ];

    public const CATEGORY_LABEL = [
        'terceirizado' => 'Terceirizados',
        'mao_de_obra' => 'Mão de obra do evento',
        'compra' => 'Compras',
        'hotel' => 'Hotel',
        'lanche' => 'Lanche da equipe',
        'bv' => 'BV de parceiros',
        'nota' => 'Notas',
        'outro' => 'Outros',
    ];

    public const REVENUE_ACCOUNT = '3.01.001';

    public const SUBTOTALS = [
        'receita_liquida' => 'Receita líquida',
        'lucro_bruto' => 'Lucro bruto',
        'ebitda' => 'Lucro operacional (EBITDA)',
        'lucro_liquido' => 'Lucro líquido',
        'lucro_retido' => 'Lucro retido',
    ];

    /** @var Collection<int, FinancialOperation> */
    private Collection $accounts;

    /** @var array<string, FinancialOperation> */
    private array $byCode = [];

    public function __construct()
    {
        $this->accounts = FinancialOperation::query()->get();
        foreach ($this->accounts as $account) {
            $this->byCode[$account->code] = $account;
        }
    }

    /** Contas ativas do plano, na ordem da estrutura. */
    public function structuredAccounts(): Collection
    {
        $sectionOrder = array_flip(array_keys(FinancialOperation::DRE_SECTIONS));
        return $this->accounts
            ->filter(fn ($a) => $a->active !== false && $a->effective_dre_section)
            ->sortBy(fn ($a) => sprintf('%02d-%06d-%s', $sectionOrder[$a->effective_dre_section] ?? 99, $a->dre_order ?? 999999, $a->code))
            ->values();
    }

    /**
     * Lançamentos do período, já com a linha do DRE: [date, amount, kind, line{key, code, label, section}, ...].
     *
     * @return array<int, array<string, mixed>>
     */
    public function entries(string $from, string $to, string $regime, array $filters = []): array
    {
        $out = [];

        // Contas a receber
        $receivables = Receivable::query()
            ->with(['financialOperation', 'receivableable'])
            ->when($regime === 'caixa', fn ($q) => $q->whereNotNull('paid_at'))
            ->get();
        foreach ($receivables as $r) {
            $event = $r->receivableable instanceof Event ? $r->receivableable : null;
            if (!empty($filters['event_id']) && (int) $filters['event_id'] !== (int) $event?->id) {
                continue;
            }
            if (!empty($filters['cost_center_id'])) {
                continue; // recebimentos não têm centro de custo
            }
            $date = $this->dateFor($regime, $r->due_date, $r->paid_at, $event);
            if (!$date || $date < $from || $date > $to) {
                continue;
            }
            $amount = $regime === 'caixa' ? (float) ($r->received_amount ?? $r->amount) : (float) $r->amount;
            $out[] = [
                'id' => $r->id,
                'kind' => 'receivable',
                'date' => $date,
                'description' => $r->description,
                'counterparty' => $event?->name,
                'event_id' => $event?->id,
                'amount' => round($amount, 2),
                'paid' => (bool) $r->paid_at,
                'line' => $this->lineFor($r->financialOperation, null, true),
            ];
        }

        // Contas a pagar (só aprovadas)
        $payables = Payable::query()
            ->with(['financialOperation', 'event:id,name,date', 'partner:id,name', 'supplier', 'collaborator:id,name'])
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->when($regime === 'caixa', fn ($q) => $q->whereNotNull('paid_at'))
            ->when(!empty($filters['event_id']), fn ($q) => $q->where('event_id', $filters['event_id']))
            ->when(!empty($filters['cost_center_id']), fn ($q) => $q->where('cost_center_id', $filters['cost_center_id']))
            ->get();
        foreach ($payables as $p) {
            $date = $this->dateFor($regime, $p->due_date, $p->paid_at, $p->event);
            if (!$date || $date < $from || $date > $to) {
                continue;
            }
            $amount = $regime === 'caixa' ? (float) ($p->paid_amount ?? $p->amount) : (float) $p->amount;
            $who = $p->partner?->name ?? $p->collaborator?->name ?? ($p->supplier ? ($p->supplier->fantasy_name ?: $p->supplier->corporate_name) : null);
            $out[] = [
                'id' => $p->id,
                'kind' => 'payable',
                'date' => $date,
                'description' => $p->description,
                'counterparty' => $who ?? $p->event?->name,
                'event_id' => $p->event_id,
                'amount' => round($amount, 2),
                'paid' => (bool) $p->paid_at,
                'line' => $this->lineFor($p->financialOperation, $p->category, false),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['date'], $b['date']));
        return $out;
    }

    /** DRE de um período: grupos com contas e lançamentos, subtotais e % sobre a receita total. */
    public function dre(string $from, string $to, string $regime, array $filters = []): array
    {
        $entries = $this->entries($from, $to, $regime, $filters);
        $groups = $this->emptyGroups(1, false);

        foreach ($entries as $e) {
            $line = $e['line'];
            $section = $line['section'];
            $value = $this->signedForSection($e, $section);
            $this->ensureLine($groups, $line, 1);
            $groups[$section]['total'][0] += $value;
            $groups[$section]['lines'][$line['key']]['values'][0] += $value;
            $groups[$section]['lines'][$line['key']]['entries'][] = [
                'id' => $e['id'],
                'kind' => $e['kind'],
                'date' => $e['date'],
                'description' => $e['description'],
                'counterparty' => $e['counterparty'],
                'event_id' => $e['event_id'],
                'amount' => $value,
                'paid' => $e['paid'],
            ];
        }

        $subtotals = $this->subtotals($groups, 1);

        return [
            'regime' => $regime,
            'from' => $from,
            'to' => $to,
            'count' => count($entries),
            'groups' => $this->finishGroups($groups, false),
            'subtotals' => array_map(fn ($s) => $s[0], $subtotals),
            'revenue' => $groups['receita_total']['total'][0],
        ];
    }

    /** ARM mensal: o mesmo demonstrativo mês a mês (jan a dez) do ano, com total e média. */
    public function arm(int $year, string $regime, int $months = 12): array
    {
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);
        $entries = $this->entries($from, $to, $regime);
        $groups = $this->emptyGroups($months, true);

        foreach ($entries as $e) {
            $m = (int) substr($e['date'], 5, 2) - 1;
            if ($m < 0 || $m >= $months) {
                continue;
            }
            $line = $e['line'];
            $section = $line['section'];
            $value = $this->signedForSection($e, $section);
            $this->ensureLine($groups, $line, $months);
            $groups[$section]['total'][$m] += $value;
            $groups[$section]['lines'][$line['key']]['values'][$m] += $value;
        }

        $subtotals = $this->subtotals($groups, $months);

        return [
            'year' => $year,
            'regime' => $regime,
            'months' => $months,
            'groups' => $this->finishGroups($groups, true),
            'subtotals' => $subtotals,
            'revenue' => $groups['receita_total']['total'],
        ];
    }

    // ---------------------------------------------------------------

    private function dateFor(string $regime, $due, $paid, ?Event $event): ?string
    {
        if ($regime === 'caixa') {
            return $paid ? $paid->format('Y-m-d') : null;
        }
        if ($regime === 'competencia' && $event && $event->date) {
            return $event->date->format('Y-m-d');
        }
        return $due ? $due->format('Y-m-d') : null;
    }

    /** Linha do DRE para a conta (ou a conta padrão da categoria). */
    private function lineFor(?FinancialOperation $account, ?string $category, bool $revenue): array
    {
        if (!$account) {
            $code = $revenue ? self::REVENUE_ACCOUNT : (self::CATEGORY_ACCOUNT[$category] ?? null);
            $account = $code ? ($this->byCode[$code] ?? null) : null;
        }
        if ($account) {
            $section = $account->effective_dre_section;
            return [
                'key' => 'acc:' . $account->id,
                'code' => $account->code,
                'label' => trim($account->code . ' ' . $account->description),
                'section' => $section ?? 'fora',
                'order' => $account->dre_order ?? 999999,
            ];
        }
        if ($revenue) {
            return ['key' => 'rev', 'code' => '', 'label' => 'Receita de eventos (sem conta no plano)', 'section' => 'receita_total', 'order' => 999999];
        }
        $label = self::CATEGORY_LABEL[$category] ?? 'Sem conta';
        $section = isset(self::CATEGORY_ACCOUNT[$category]) ? 'custos_variaveis' : 'nao_classificado';
        return ['key' => 'cat:' . ($category ?: 'sem'), 'code' => '', 'label' => $label . ' (sem conta no plano)', 'section' => $section, 'order' => 999999];
    }

    /**
     * Valor do lançamento no grupo: recebimento soma em grupo de receita; pagamento soma em grupo de despesa.
     * Se cair "ao contrário" (ex.: estorno), entra negativo.
     */
    private function signedForSection(array $e, string $section): float
    {
        $revenueGroup = in_array($section, ['receita_total', 'receitas_nao_operacionais'], true);
        if ($section === 'nao_classificado' || $section === 'fora') {
            return $e['kind'] === 'receivable' ? $e['amount'] : -$e['amount'];
        }
        $isRevenue = $e['kind'] === 'receivable';
        return $revenueGroup === $isRevenue ? $e['amount'] : -$e['amount'];
    }

    private function emptyGroups(int $months, bool $withAllAccounts): array
    {
        $groups = [];
        foreach (FinancialOperation::DRE_SECTIONS as $key => $meta) {
            $groups[$key] = ['key' => $key, 'label' => $meta['label'], 'sign' => $meta['sign'], 'subtotal' => $meta['subtotal'], 'total' => array_fill(0, $months, 0.0), 'lines' => []];
        }
        $groups['nao_classificado'] = ['key' => 'nao_classificado', 'label' => 'Sem classificação', 'sign' => -1, 'subtotal' => null, 'total' => array_fill(0, $months, 0.0), 'lines' => []];
        $groups['fora'] = ['key' => 'fora', 'label' => 'Fora do DRE', 'sign' => -1, 'subtotal' => null, 'total' => array_fill(0, $months, 0.0), 'lines' => []];

        if ($withAllAccounts) {
            // Como no ARM da MAV: toda conta ativa aparece, mesmo sem movimento.
            foreach ($this->structuredAccounts() as $account) {
                $this->ensureLine($groups, $this->lineFor($account, null, false), $months);
            }
        }
        return $groups;
    }

    private function ensureLine(array &$groups, array $line, int $months): void
    {
        $section = $line['section'];
        if (!isset($groups[$section]['lines'][$line['key']])) {
            $groups[$section]['lines'][$line['key']] = [
                'key' => $line['key'],
                'code' => $line['code'],
                'label' => $line['label'],
                'order' => $line['order'],
                'values' => array_fill(0, $months, 0.0),
                'entries' => [],
            ];
        }
    }

    private function subtotals(array $groups, int $months): array
    {
        $t = fn ($k) => $groups[$k]['total'];
        $sub = fn ($a, $b) => array_map(fn ($x, $y) => round($x - $y, 2), $a, $b);
        $add = fn ($a, $b) => array_map(fn ($x, $y) => round($x + $y, 2), $a, $b);

        $receitaLiquida = $sub($t('receita_total'), $t('deducoes'));
        $lucroBruto = $sub($receitaLiquida, $t('custos_variaveis'));
        $ebitda = $sub($lucroBruto, $t('despesas_operacionais'));
        // "Sem classificação" já vem com sinal (+ recebido, − pago)
        $lucroLiquido = $add($add($sub($ebitda, $t('despesas_nao_operacionais')), $t('receitas_nao_operacionais')), $t('nao_classificado'));
        $lucroRetido = $sub($lucroLiquido, $t('retirada_socios'));

        return [
            'receita_liquida' => $receitaLiquida,
            'lucro_bruto' => $lucroBruto,
            'ebitda' => $ebitda,
            'lucro_liquido' => $lucroLiquido,
            'lucro_retido' => $lucroRetido,
        ];
    }

    private function finishGroups(array $groups, bool $series): array
    {
        $out = [];
        foreach ($groups as $key => $g) {
            if (in_array($key, ['deducoes', 'nao_classificado', 'fora'], true) && empty($g['lines'])) {
                continue;
            }
            $lines = array_values($g['lines']);
            usort($lines, fn ($a, $b) => [$a['order'], $a['code'], $a['label']] <=> [$b['order'], $b['code'], $b['label']]);
            $lines = array_map(function ($l) use ($series) {
                $l['values'] = array_map(fn ($v) => round($v, 2), $l['values']);
                if ($series) {
                    unset($l['entries']);
                } else {
                    $l['value'] = $l['values'][0];
                    unset($l['values']);
                }
                unset($l['order']);
                return $l;
            }, $lines);
            $total = array_map(fn ($v) => round($v, 2), $g['total']);
            $out[] = [
                'key' => $key,
                'label' => $g['label'],
                'sign' => $g['sign'],
                'subtotal_after' => $g['subtotal'],
                'subtotal_key' => $this->subtotalKeyFor($key),
                'total' => $series ? $total : $total[0],
                'lines' => $lines,
            ];
        }
        return $out;
    }

    private function subtotalKeyFor(string $section): ?string
    {
        return [
            'deducoes' => 'receita_liquida',
            'custos_variaveis' => 'lucro_bruto',
            'despesas_operacionais' => 'ebitda',
            'receitas_nao_operacionais' => 'lucro_liquido',
            'retirada_socios' => 'lucro_retido',
        ][$section] ?? null;
    }
}
