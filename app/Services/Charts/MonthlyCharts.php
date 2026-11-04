<?php

namespace App\Services\Charts;

use App\Models\Event;
use App\Models\EventStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class MonthlyCharts extends Charts
{
    protected const CASTS = [
        'events' => 'int',
        'guests' => 'int',
        'previous_events' => 'int',
        'total_value' => 'float',
        'average_ticket' => 'float',
        'received' => 'float',
        'previous_total_value' => 'float',
        'previous_received' => 'float',
        'opportunities' => 'int',
        'negotiating' => 'int',
        'lost' => 'int',
        'conversion_rate' => 'rate',
    ];

    private int $year;

    // Os valores só saem para quem vê os gráficos financeiros, e a demanda só para quem vê os comerciais.
    public function __construct(array $filters, private bool $financial, private bool $commercial)
    {
        parent::__construct(array_diff_key($filters, array_flip(['period_start', 'period_end', 'date_field', 'status_ids'])));
        $this->year = (int) ($filters['year'] ?? now()->year);
    }

    public function data(): array
    {
        return [
            'year' => $this->year,
            'years' => $this->years(),
            'months' => $this->months(),
            'weekdays' => $this->weekdays(),
            'heatmap' => $this->heatmap(),
        ];
    }

    private function months(): array
    {
        $from = ($this->year - 1) . '-01-01';
        $to = "{$this->year}-12-31";
        $month = "DATE_FORMAT(events.date, '%Y-%m')";

        $confirmed = $this->grouped(
            $this->confirmed()->whereBetween('events.date', [$from, $to]),
            $month,
            [
                'events' => 'COUNT(events.id)',
                'guests' => 'SUM(events.event_guests)',
                'total_value' => 'SUM(events.total_value)',
                'average_ticket' => 'SUM(events.total_value) / NULLIF(SUM(events.total_value > 0), 0)',
            ],
        );

        $received = $this->financial ? $this->received($from, $to) : collect();

        $demand = $this->commercial
            ? $this->grouped(
                $this->events()->toBase()->whereYear('events.date', $this->year),
                $month,
                [
                    'opportunities' => 'COUNT(events.id)',
                    'negotiating' => 'SUM(' . $this->inStatuses(EventStatus::NEGOTIATING) . ')',
                    'lost' => 'SUM(' . $this->inStatuses(EventStatus::LOST) . ')',
                ],
            )
            : collect();

        return collect(range(1, 12))->map(function (int $number) use ($confirmed, $received, $demand) {
            $month = sprintf('%d-%02d', $this->year, $number);
            $previous = sprintf('%d-%02d', $this->year - 1, $number);

            $row = [
                'month' => $month,
                'events' => $confirmed[$month]->events ?? 0,
                'guests' => $confirmed[$month]->guests ?? 0,
                'previous_events' => $confirmed[$previous]->events ?? 0,
            ];

            if ($this->financial) {
                $row += [
                    'total_value' => $confirmed[$month]->total_value ?? 0,
                    'average_ticket' => $confirmed[$month]->average_ticket ?? null,
                    'received' => $received[$month]->received ?? 0,
                    'previous_total_value' => $confirmed[$previous]->total_value ?? 0,
                    'previous_received' => $received[$previous]->received ?? 0,
                ];
            }

            if ($this->commercial) {
                $lost = (int) ($demand[$month]->lost ?? 0);

                // Mesma taxa dos relatórios: confirmados sobre os que já tiveram desfecho.
                $row += [
                    'opportunities' => $demand[$month]->opportunities ?? 0,
                    'negotiating' => $demand[$month]->negotiating ?? 0,
                    'lost' => $lost,
                    'conversion_rate' => $row['events'] + $lost > 0 ? $row['events'] / ($row['events'] + $lost) : null,
                ];
            }

            return $this->cast($row);
        })->all();
    }

    private function received(string $from, string $to): Collection
    {
        $query = $this->events()
            ->toBase()
            ->join('event_payments', 'event_payments.event_id', '=', 'events.id')
            ->whereNull('event_payments.deleted_at')
            ->whereBetween('event_payments.date', [$from, $to]);

        return $this->grouped($query, "DATE_FORMAT(event_payments.date, '%Y-%m')", ['received' => 'SUM(event_payments.value)']);
    }

    private function weekdays(): array
    {
        $rows = $this->grouped(
            $this->confirmed()->whereYear('events.date', $this->year),
            'WEEKDAY(events.date)',
            ['events' => 'COUNT(events.id)'],
        );

        return collect(range(0, 6))
            ->map(fn (int $day) => ['weekday' => $day + 1, 'events' => (int) ($rows[$day]->events ?? 0)])
            ->all();
    }

    private function heatmap(): array
    {
        $metrics = ['events' => 'COUNT(events.id)'] + ($this->financial ? ['total_value' => 'SUM(events.total_value)'] : []);

        return $this->grouped(
            $this->confirmed()->whereNotNull('events.date'),
            "DATE_FORMAT(events.date, '%Y-%m')",
            $metrics,
        )
            ->groupBy(fn (object $row) => (int) substr($row->bucket, 0, 4))
            ->sortKeys()
            ->map(function (Collection $rows, int $year) use ($metrics) {
                $months = $rows->keyBy(fn (object $row) => (int) substr($row->bucket, 5, 2));
                $cells = ['year' => $year];

                foreach (array_keys($metrics) as $metric) {
                    $cells[$metric] = collect(range(1, 12))
                        ->map(fn (int $month) => $metric === 'events' ? (int) ($months[$month]->events ?? 0) : round((float) ($months[$month]->{$metric} ?? 0), 2))
                        ->all();
                }

                return $cells;
            })
            ->values()
            ->all();
    }

    private function years(): array
    {
        return Event::query()
            ->where('is_draft', false)
            ->whereNotNull('date')
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->push((int) now()->year, $this->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function confirmed(): Builder
    {
        return $this->events()->toBase()->whereRaw($this->inStatuses(EventStatus::CONFIRMED));
    }

    private function grouped(Builder $query, string $key, array $metrics): Collection
    {
        $query->selectRaw("{$key} as bucket");

        foreach ($metrics as $alias => $expression) {
            $query->selectRaw("{$expression} as {$alias}");
        }

        return $query->groupByRaw($key)->get()->keyBy('bucket');
    }
}
