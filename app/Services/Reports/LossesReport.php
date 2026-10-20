<?php

namespace App\Services\Reports;

use App\Models\EventStatus;
use Illuminate\Database\Eloquent\Builder;

class LossesReport extends GroupedReport
{
    public const PERMISSION = 'relatorios-comercial';

    protected const DEFAULT_DATE_FIELD = 'created_at';

    protected const GROUPS = [
        'lost_reason',
        'competitor',
        'status',
        'collaborator',
        'event_type',
        'event_capture',
        'month',
    ];

    protected const CASTS = [
        'events' => 'int',
        'guests' => 'int',
        'total_value' => 'float',
        'lost_to_competitor' => 'int',
    ];

    protected function base(): Builder
    {
        return $this->events()->whereRaw('events.event_status_id IN (' . $this->statusIds(EventStatus::LOST) . ')');
    }

    protected function metrics(): array
    {
        return [
            'events' => 'COUNT(events.id)',
            'guests' => 'SUM(events.event_guests)',
            'total_value' => 'SUM(events.total_value)',
        ];
    }

    protected function defaultSort(): array
    {
        return $this->groupKey() === 'month' ? ['label', 'asc'] : ['events', 'desc'];
    }

    protected function present(object $row): array
    {
        $row = parent::present($row);
        $row['share'] = $this->share($row['events'] ?? 0, $this->summary()['events'] ?? 0);

        return $row;
    }

    protected function totals(): array
    {
        $totals = $this->aggregate($this->base(), $this->metrics() + [
            'lost_to_competitor' => 'SUM(events.event_status_id IN (' . $this->statusIds(['Fechou com o concorrente']) . '))',
        ]);

        // A taxa compara com todos os eventos do período: status, motivo e concorrente só existem nos perdidos.
        $all = (new static(array_diff_key($this->filters, array_flip(['status_ids', 'lost_reason_ids', 'competitor_ids']))))
            ->events()
            ->count();

        $mainReason = $this->base()
            ->toBase()
            ->select('lost_reasons.name')
            ->whereNotNull('events.lost_reason_id')
            ->groupBy('lost_reasons.id', 'lost_reasons.name')
            ->orderByRaw('COUNT(events.id) DESC')
            ->value('lost_reasons.name');

        return $totals + [
            'loss_rate' => $this->share($totals['events'] ?? 0, $all),
            'main_reason' => $mainReason,
        ];
    }
}
