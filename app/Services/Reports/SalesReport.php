<?php

namespace App\Services\Reports;

use App\Models\EventStatus;

class SalesReport extends GroupedReport
{
    public const PERMISSION = 'relatorios-comercial';

    protected const DEFAULT_DATE_FIELD = 'created_at';

    protected const GROUPS = [
        'collaborator',
        'event_type',
        'event_capture',
        'how_did_you_hear',
        'partner',
        'city',
        'month',
    ];

    protected const CASTS = [
        'total' => 'int',
        'negotiating' => 'int',
        'confirmed' => 'int',
        'lost' => 'int',
        'conversion_rate' => 'rate',
        'confirmed_value' => 'float',
        'pipeline_value' => 'float',
        'average_ticket' => 'float',
    ];

    private ?array $metrics = null;

    protected function metrics(): array
    {
        if ($this->metrics) {
            return $this->metrics;
        }

        $negotiating = 'events.event_status_id IN (' . $this->statusIds(EventStatus::NEGOTIATING) . ')';
        $confirmed = 'events.event_status_id IN (' . $this->statusIds(EventStatus::CONFIRMED) . ')';
        $lost = 'events.event_status_id IN (' . $this->statusIds(EventStatus::LOST) . ')';

        return $this->metrics = [
            'total' => 'COUNT(events.id)',
            'negotiating' => "SUM({$negotiating})",
            'confirmed' => "SUM({$confirmed})",
            'lost' => "SUM({$lost})",
            // Só conta o que já teve desfecho: eventos ainda em negociação não entram na taxa.
            'conversion_rate' => "SUM({$confirmed}) / NULLIF(SUM({$confirmed}) + SUM({$lost}), 0)",
            'confirmed_value' => "SUM(CASE WHEN {$confirmed} THEN events.total_value END)",
            'pipeline_value' => "SUM(CASE WHEN {$negotiating} THEN events.total_value END)",
            'average_ticket' => "SUM(CASE WHEN {$confirmed} THEN events.total_value END) / NULLIF(SUM({$confirmed} AND events.total_value > 0), 0)",
        ];
    }

    protected function defaultSort(): array
    {
        return $this->groupKey() === 'month' ? ['label', 'asc'] : ['total', 'desc'];
    }

    protected function totals(): array
    {
        return $this->aggregate($this->base(), $this->metrics());
    }
}
