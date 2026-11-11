<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadStageChange;
use App\Models\LostReason;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Gráficos do Comercial (modelos do Pipedrive enviados pela gerência em 06/11/2026).
 * Valor do negócio = valor final; sem ele, valor da proposta. Faturamento conta pela data do Ganho.
 */
class LeadDashboardService
{
    public const CHARTS = ['faturamento_mensal', 'motivos_perda', 'faturamento_consultor', 'conversao', 'status_vendedor', 'progressao'];

    public const PERIODS = [
        'este_mes' => 'Este mês',
        'mes_passado' => 'Mês passado',
        'ultimos_3_meses' => 'Últimos 3 meses',
        'este_ano' => 'Este ano',
        'ultimos_12_meses' => 'Últimos 12 meses',
        'ano_passado' => 'Ano passado',
    ];

    public const STAGE_LABELS = [
        'novo' => 'Recebimento de leads',
        'em_contato' => 'Em contato | Qualificação',
        'reuniao' => 'Reunião agendada',
        'proposta_enviada' => 'Proposta enviada',
        'follow_up' => 'Follow up',
        'negociacao' => 'Negociação | Fechamento',
        'aguardando_assinatura' => 'Aguardando assinatura',
        'nutricao' => 'Nutrição',
        'licitacao' => 'Licitações',
        'ganho' => 'Ganho',
        'perdido' => 'Perdido',
    ];

    public const SOURCE_LABELS = [
        'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'trafego_pago' => 'Tráfego pago', 'site' => 'Site',
        'cerimonial' => 'Cerimonial / Produtora', 'indicacao' => 'Indicação', 'direto' => 'Direto com o consultor',
        'prospeccao' => 'Prospecção', 'sdr' => 'SDR', 'licitacao' => 'Licitação', 'outro' => 'Outro',
    ];

    /** @return array{0: Carbon, 1: Carbon} */
    public function range(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'este_mes' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'mes_passado' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'ultimos_3_meses' => [$now->copy()->subMonthsNoOverflow(2)->startOfMonth(), $now->copy()->endOfMonth()],
            'ultimos_12_meses' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->copy()->endOfMonth()],
            'ano_passado' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }

    /** Negócios usados no gráfico (para exportar para o Excel). */
    private Collection $used;

    public function chart(string $chart, string $period, Builder $scope, bool $withDeals = false): array
    {
        $this->used = collect();
        [$from, $to] = $this->range($period);
        $data = match ($chart) {
            'faturamento_mensal' => $this->monthlyRevenue($from, $to, $scope),
            'motivos_perda' => $this->lostReasons($from, $to, $scope),
            'faturamento_consultor' => $this->revenueByConsultant($from, $to, $scope),
            'conversao' => $this->conversion($from, $to, $scope),
            'status_vendedor' => $this->statusBySeller($from, $to, $scope),
            default => $this->progression($from, $to, $scope),
        };

        $out = ['chart' => $chart, 'period' => $period, 'period_label' => self::PERIODS[$period] ?? self::PERIODS['este_ano'], 'from' => $from->toDateString(), 'to' => $to->toDateString()] + $data;
        if ($withDeals) {
            $out['deals'] = $this->deals();
        }

        return $out;
    }

    /** Linhas dos negócios por trás do gráfico, como no "exportar" do Pipedrive. */
    private function deals(): array
    {
        $leads = new \Illuminate\Database\Eloquent\Collection($this->used->unique('id')->values()->all());
        $leads->load(['user:id,name', 'eventType:id,name', 'lostReason', 'partner']);
        $date = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : null;

        return $leads->sortBy('id')->map(fn (Lead $l) => [
            'ID' => $l->id,
            'Negócio' => $l->name,
            'Consultor' => $this->userName($l),
            'Etapa' => self::STAGE_LABELS[$l->stage] ?? $l->stage,
            'Tipo de evento' => $l->eventType?->name,
            'Data do evento' => $date($l->event_date),
            'Cidade' => $l->city,
            'Convidados' => $l->guests !== null ? (int) $l->guests : null,
            'Valor (R$)' => $this->value($l),
            'Viabilidade (%)' => ($l->real_viability ?? $l->expected_viability) !== null ? (float) ($l->real_viability ?? $l->expected_viability) : null,
            'Origem' => self::SOURCE_LABELS[$l->source] ?? $l->source,
            'Parceiro' => $l->partner?->name,
            'Criado em' => $date($l->created_at),
            'Fechado em' => isset($l->closed_at) ? $date($l->closed_at) : null,
            'Veio da etapa' => isset($l->closed_from) ? (self::STAGE_LABELS[$l->closed_from] ?? $l->closed_from) : null,
            'Motivo da perda' => $l->lostReason?->name,
        ])->values()->all();
    }

    // ------------------------------------------------------------------ apoio

    /** Leads fechados (ganho/perdido) no período, com a data e a etapa de onde vieram. */
    private function closed(string $stage, Carbon $from, Carbon $to, Builder $scope): Collection
    {
        $leads = (clone $scope)->where('stage', $stage)->with(['user:id,name', 'lostReason'])->get()->keyBy('id');
        if ($leads->isEmpty()) {
            return collect();
        }
        $changes = LeadStageChange::whereIn('lead_id', $leads->keys())->where('to_stage', $stage)->orderBy('changed_at')->get()->groupBy('lead_id');

        return $leads->map(function (Lead $lead) use ($changes) {
            $last = $changes->get($lead->id)?->last();
            $lead->closed_at = $last?->changed_at ?? $lead->stage_changed_at ?? $lead->updated_at;
            $lead->closed_from = $last?->from_stage;

            return $lead;
        })->filter(fn (Lead $l) => $l->closed_at && $l->closed_at->between($from, $to))->values()->each(fn ($l) => $this->used->push($l));
    }

    private function value(Lead $lead): float
    {
        return (float) ($lead->final_value ?? $lead->estimated_value ?? 0);
    }

    /** Meses do período; período curto (menos de 3 meses) mostra os 12 meses até o fim dele. */
    private function months(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfMonth();
        if ($start->diffInMonths($to) < 2) {
            $start = $to->copy()->subMonthsNoOverflow(11)->startOfMonth();
        }
        $months = [];
        for ($m = $start->copy(); $m->lte($to); $m->addMonthNoOverflow()) {
            $months[] = $m->format('Y-m');
        }

        return $months;
    }

    private function monthLabel(string $ym): string
    {
        $names = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        [$y, $m] = explode('-', $ym);

        return $names[(int) $m - 1] . ' ' . $y;
    }

    /** Nome do consultor; sem usuário, vale o proprietário que veio do Pipedrive. */
    private function userName(Lead $lead): string
    {
        return $lead->user?->name ?? ($lead->imported_owner ?: 'Sem consultor');
    }

    // ------------------------------------------------------------------ gráficos

    /** 1. Faturamento mensal: valor dos negócios ganhos por mês do Ganho. */
    private function monthlyRevenue(Carbon $from, Carbon $to, Builder $scope): array
    {
        $months = $this->months($from, $to);
        $won = $this->closed('ganho', Carbon::createFromFormat('Y-m', $months[0])->startOfMonth(), $to, $scope);
        $byMonth = $won->groupBy(fn (Lead $l) => $l->closed_at->format('Y-m'));

        return [
            'bars' => array_map(fn ($ym) => [
                'label' => $this->monthLabel($ym),
                'value' => round((float) ($byMonth->get($ym)?->sum(fn ($l) => $this->value($l)) ?? 0), 2),
                'count' => $byMonth->get($ym)?->count() ?? 0,
            ], $months),
            'total' => round($won->sum(fn ($l) => $this->value($l)), 2),
        ];
    }

    /** 2. Motivos de perda: quantidade e % dos leads perdidos no período. */
    private function lostReasons(Carbon $from, Carbon $to, Builder $scope): array
    {
        $lost = $this->closed('perdido', $from, $to, $scope);
        $total = $lost->count();
        $slices = $lost->groupBy(fn (Lead $l) => $l->lostReason?->name ?? 'Sem motivo')
            ->map(fn ($g, $name) => ['label' => $name, 'value' => $g->count(), 'percent' => $total ? round($g->count() * 100 / $total) : 0])
            ->sortByDesc('value')->values()->all();

        return ['slices' => $slices, 'total' => $total];
    }

    /** 3. Faturamento total por consultor: ganhos no período, empilhados pela etapa de onde o lead foi para Ganho. */
    private function revenueByConsultant(Carbon $from, Carbon $to, Builder $scope): array
    {
        $won = $this->closed('ganho', $from, $to, $scope);
        $segments = [];
        $bars = $won->groupBy(fn (Lead $l) => $this->userName($l))->map(function ($group, $name) use (&$segments) {
            $parts = $group->groupBy(fn (Lead $l) => $l->closed_from ?: 'novo')->map(fn ($g) => round($g->sum(fn ($l) => $this->value($l)), 2));
            foreach ($parts->keys() as $k) {
                $segments[$k] = true;
            }

            return ['label' => $name, 'total' => round($group->sum(fn ($l) => $this->value($l)), 2), 'parts' => $parts->all()];
        })->sortByDesc('total')->values()->all();

        $order = array_values(array_filter(array_keys(self::STAGE_LABELS), fn ($s) => isset($segments[$s])));

        return [
            'bars' => $bars,
            'segments' => array_map(fn ($s) => ['key' => $s, 'label' => self::STAGE_LABELS[$s]], $order),
            'total' => round($won->sum(fn ($l) => $this->value($l)), 2),
        ];
    }

    /** 4. Conversão geral: por mês, taxa de ganho (para cima) e de perda (para baixo) dos leads fechados. */
    private function conversion(Carbon $from, Carbon $to, Builder $scope): array
    {
        $months = $this->months($from, $to);
        $start = Carbon::createFromFormat('Y-m', $months[0])->startOfMonth();
        $won = $this->closed('ganho', $start, $to, $scope)->groupBy(fn ($l) => $l->closed_at->format('Y-m'));
        $lost = $this->closed('perdido', $start, $to, $scope)->groupBy(fn ($l) => $l->closed_at->format('Y-m'));

        return [
            'bars' => array_map(function ($ym) use ($won, $lost) {
                $w = $won->get($ym)?->count() ?? 0;
                $p = $lost->get($ym)?->count() ?? 0;
                $t = $w + $p;

                return [
                    'label' => $this->monthLabel($ym),
                    'won' => $w,
                    'lost' => $p,
                    'win_rate' => $t ? round($w * 100 / $t) : null,
                    'loss_rate' => $t ? 100 - round($w * 100 / $t) : null,
                ];
            }, $months),
        ];
    }

    /** 5. Status dos negócios por vendedor: leads criados no período, abertos, perdidos e ganhos. */
    private function statusBySeller(Carbon $from, Carbon $to, Builder $scope): array
    {
        $leads = (clone $scope)->whereBetween('created_at', [$from, $to])->with('user:id,name')->get();
        $this->used = $this->used->merge($leads);
        $bars = $leads->groupBy(fn (Lead $l) => $this->userName($l))->map(fn ($g, $name) => [
            'label' => $name,
            'aberto' => $g->whereNotIn('stage', ['ganho', 'perdido'])->count(),
            'perdido' => $g->where('stage', 'perdido')->count(),
            'ganho' => $g->where('stage', 'ganho')->count(),
            'total' => $g->count(),
        ])->sortByDesc('total')->values()->all();

        return ['bars' => $bars, 'total' => $leads->count()];
    }

    /** 6. Progressão do funil: leads criados no período que chegaram a cada etapa do caminho principal. */
    private function progression(Carbon $from, Carbon $to, Builder $scope): array
    {
        $leads = (clone $scope)->whereBetween('created_at', [$from, $to])->get();
        $this->used = $this->used->merge($leads);
        $path = Lead::MAIN_PATH;
        $index = array_flip($path);
        $changes = LeadStageChange::whereIn('lead_id', $leads->pluck('id'))->get(['lead_id', 'to_stage'])->groupBy('lead_id');

        $reached = array_fill(0, count($path), 0);
        foreach ($leads as $lead) {
            $stages = ($changes->get($lead->id)?->pluck('to_stage') ?? collect())->push($lead->stage);
            $max = $stages->map(fn ($s) => $index[$s] ?? -1)->max();
            if ($lead->stage === 'ganho') {
                $max = count($path) - 1;
            }
            for ($i = 0; $i <= max($max, 0); $i++) {
                $reached[$i]++;
            }
        }

        $bars = [];
        foreach ($path as $i => $stage) {
            $bars[] = [
                'key' => $stage,
                'label' => self::STAGE_LABELS[$stage],
                'value' => $reached[$i],
                'pass_rate' => $i > 0 && $reached[$i - 1] ? round($reached[$i] * 100 / $reached[$i - 1]) : null,
            ];
        }
        $created = $leads->count();

        return [
            'bars' => $bars,
            'created' => $created,
            'win_rate' => $created ? round($reached[count($path) - 1] * 100 / $created) : 0,
        ];
    }
}
