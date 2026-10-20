<?php

namespace App\Services\Reports;

class RevenueReport extends GroupedReport
{
    protected const GROUPS = [
        'city',
        'state',
        'client',
        'event_place',
        'event_type',
        'collaborator',
        'partner',
        'status',
        'event_capture',
        'how_did_you_hear',
        'month',
    ];

    protected const CASTS = [
        'events' => 'int',
        'guests' => 'int',
        'total_value' => 'float',
        'average_ticket' => 'float',
        'paid' => 'float',
        'balance' => 'float',
    ];

    protected function metrics(): array
    {
        return $this->revenueMetrics();
    }

    protected function defaultSort(): array
    {
        return $this->groupKey() === 'month' ? ['label', 'asc'] : ['total_value', 'desc'];
    }

    protected function present(object $row): array
    {
        $row = parent::present($row);
        $row['share'] = $this->share($row['total_value'] ?? 0, $this->summary()['total_value'] ?? 0);

        return $row;
    }

    protected function totals(): array
    {
        return $this->aggregate($this->base(), $this->metrics());
    }
}
