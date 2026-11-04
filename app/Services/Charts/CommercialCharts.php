<?php

namespace App\Services\Charts;

use App\Models\EventStatus;
use App\Services\Reports\LossesReport;
use App\Services\Reports\SalesReport;
use Illuminate\Support\Facades\DB;

class CommercialCharts extends Charts
{
    public const PERMISSION = 'graficos-comercial';

    public const DIMENSIONS = ['event_capture', 'how_did_you_hear', 'collaborator', 'partner', 'event_type', 'city'];

    protected const DATE_FIELDS = [
        'date' => 'events.date',
        'created_at' => 'events.created_at',
    ];

    protected const CASTS = [
        'total' => 'int',
        'negotiating' => 'int',
        'confirmed' => 'int',
        'lost' => 'int',
        'events' => 'int',
        'guests' => 'int',
        'conversion_rate' => 'rate',
        'confirmed_value' => 'float',
        'pipeline_value' => 'float',
        'total_value' => 'float',
        'share' => 'rate',
    ];

    // Planejamento e Concluído contam como Fechado no funil: só chegam lá depois dele.
    private const STAGES = [
        1 => ['Aberto'],
        2 => ['Pré-Reserva'],
        3 => ['Contrato'],
        4 => ['Fechado', 'Planejamento', 'Concluído'],
    ];

    private const FUNNEL = [
        1 => 'Oportunidades',
        2 => 'Pré-Reserva',
        3 => 'Contrato',
        4 => 'Fechado',
    ];

    private const LEAD_TIME_BANDS = [
        [30, 'Menos de 1 mês'],
        [90, '1 a 3 meses'],
        [180, '3 a 6 meses'],
        [365, '6 meses a 1 ano'],
        [null, 'Mais de 1 ano'],
    ];

    public function __construct(array $filters = [])
    {
        $dateField = array_key_exists($filters['date_field'] ?? '', self::DATE_FIELDS) ? $filters['date_field'] : 'date';

        // O status é o próprio resultado da venda, então não entra como filtro aqui.
        parent::__construct(['date_field' => $dateField] + array_diff_key($filters, ['status_ids' => true]));
    }

    public function data(): array
    {
        $leadTime = $this->leadTime();

        return [
            'summary' => (new SalesReport($this->filters))->summary() + ['average_lead_days' => $leadTime['average_days']],
            'funnel' => $this->funnel(),
            'breakdowns' => collect(self::DIMENSIONS)->mapWithKeys(fn (string $dimension) => [$dimension => $this->breakdown($dimension)]),
            'lost_reasons' => $this->losses('lost_reason'),
            'competitors' => $this->losses('competitor'),
            'lead_time' => $leadTime['bands'],
        ];
    }

    private function breakdown(string $dimension): array
    {
        $rows = (new SalesReport(['group_by' => $dimension] + $this->filters))->all();

        return $this->top(
            $rows,
            ['total', 'negotiating', 'confirmed', 'lost', 'confirmed_value', 'pipeline_value'],
            fn (array $others) => $others + [
                'conversion_rate' => $others['confirmed'] + $others['lost'] > 0 ? $others['confirmed'] / ($others['confirmed'] + $others['lost']) : null,
                'average_ticket' => null,
            ],
        );
    }

    private function losses(string $groupBy): array
    {
        $report = new LossesReport(['group_by' => $groupBy] + $this->filters);
        $rows = $report->all();

        if ($groupBy === 'competitor') {
            $rows = $rows->whereNotNull('group_id');
        }

        return $this->top(
            $rows,
            ['events', 'guests', 'total_value'],
            fn (array $others) => $others + ['share' => $this->share($others['events'], (float) $report->summary()['events'])],
        );
    }

    // Usa o histórico de status para que um evento perdido em Contrato ainda conte como tendo chegado ao Contrato.
    private function funnel(): array
    {
        $history = DB::table('event_status_histories')
            ->selectRaw('event_id, MAX(' . $this->stage('event_status_id') . ') as stage')
            ->whereNull('deleted_at')
            ->groupBy('event_id');

        $reached = 'GREATEST(' . $this->stage('events.event_status_id') . ', COALESCE(status_history.stage, 0))';

        $query = $this->events()
            ->leftJoinSub($history, 'status_history', 'status_history.event_id', '=', 'events.id')
            ->toBase();

        foreach (array_keys(self::FUNNEL) as $rank) {
            $query->selectRaw($rank === 1 ? "COUNT(events.id) as stage_{$rank}" : "SUM({$reached} >= {$rank}) as stage_{$rank}");
        }

        $row = $query->first();

        return collect(self::FUNNEL)
            ->map(fn (string $label, int $rank) => ['label' => $label, 'events' => (int) ($row->{"stage_{$rank}"} ?? 0)])
            ->values()
            ->all();
    }

    private function stage(string $column): string
    {
        $cases = collect(self::STAGES)
            ->map(fn (array $names, int $rank) => "WHEN {$column} IN ({$this->statusIds($names)}) THEN {$rank}")
            ->implode(' ');

        return "CASE {$cases} ELSE 0 END";
    }

    private function leadTime(): array
    {
        $days = 'DATEDIFF(events.date, events.created_at)';
        $cases = collect(self::LEAD_TIME_BANDS)
            ->map(fn (array $band, int $index) => $band[0] ? "WHEN {$days} < {$band[0]} THEN {$index}" : "ELSE {$index}")
            ->implode(' ');
        $band = "CASE {$cases} END";

        $rows = $this->events()
            ->toBase()
            ->whereRaw($this->inStatuses(EventStatus::CONFIRMED))
            // Os eventos importados do sistema antigo têm a data da importação como cadastro.
            ->whereNull('events.old_system_id')
            ->whereRaw("{$days} >= 0")
            ->selectRaw("{$band} as band")
            ->selectRaw('COUNT(events.id) as events')
            ->selectRaw("SUM({$days}) as days")
            ->groupByRaw($band)
            ->get()
            ->keyBy('band');

        $events = (int) $rows->sum('events');

        return [
            'average_days' => $events ? (int) round($rows->sum('days') / $events) : null,
            'bands' => collect(self::LEAD_TIME_BANDS)
                ->map(fn (array $band, int $index) => ['label' => $band[1], 'events' => (int) ($rows[$index]->events ?? 0)])
                ->all(),
        ];
    }
}
