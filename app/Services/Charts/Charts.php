<?php

namespace App\Services\Charts;

use App\Services\Reports\EventDataset;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

abstract class Charts extends EventDataset
{
    protected const TOP = 8;

    abstract public function data(): array;

    protected function inStatuses(array $names): string
    {
        return 'events.event_status_id IN (' . $this->statusIds($names) . ')';
    }

    // Rankings mostram os maiores e somam a cauda numa barra "Outros", para o gráfico não virar uma lista.
    protected function top(Collection $rows, array $sums, callable $derive): array
    {
        $rows = $rows->values();
        $rest = $rows->slice(self::TOP);
        $top = $rows->take(self::TOP)->map(fn (array $row) => $this->compact($row));

        if ($rest->isNotEmpty()) {
            $others = ['id' => null, 'label' => "Outros ({$rest->count()})"];

            foreach ($sums as $key) {
                $others[$key] = $rest->sum($key);
            }

            $top->push($this->cast($derive($others)));
        }

        return $top->all();
    }

    protected function monthly(Collection $rows, array $keys): array
    {
        $rows = $rows->keyBy('month');
        $start = $this->filters['period_start'] ?? $rows->keys()->min();
        $end = $this->filters['period_end'] ?? $rows->keys()->max();

        if (!$start || !$end || $start > $end) {
            return [];
        }

        $period = CarbonPeriod::create(
            Carbon::createFromFormat('!Y-m', substr($start, 0, 7)),
            '1 month',
            Carbon::createFromFormat('!Y-m', substr($end, 0, 7)),
        );

        return collect($period)->map(function (Carbon $date) use ($rows, $keys) {
            $row = (array) ($rows[$date->format('Y-m')] ?? []);
            $values = ['month' => $date->format('Y-m')];

            foreach ($keys as $key) {
                $values[$key] = $row[$key] ?? 0;
            }

            return $this->cast($values);
        })->all();
    }

    private function compact(array $row): array
    {
        $label = $row['label'];

        if (isset($row['state']) && !isset($row['city'])) {
            $label .= " - {$row['state']}";
        }

        return ['id' => $row['group_id'], 'label' => $label] + array_diff_key($row, array_flip(['group_id', 'label', 'state', 'city']));
    }
}
