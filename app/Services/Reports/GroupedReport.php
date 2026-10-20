<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

abstract class GroupedReport extends Report
{
    // O primeiro agrupamento da lista é o padrão.
    protected const GROUPS = [];

    abstract protected function metrics(): array;

    public function groupKeys(): array
    {
        return static::GROUPS;
    }

    protected function groupKey(): string
    {
        $key = $this->filters['group_by'] ?? null;

        return in_array($key, static::GROUPS, true) ? $key : static::GROUPS[0];
    }

    protected function group(): array
    {
        return $this->groupDefinitions()[$this->groupKey()];
    }

    protected function base(): Builder
    {
        return $this->events();
    }

    protected function query(): Builder
    {
        $group = $this->group();
        $extras = $group['extras'] ?? [];

        $query = $this->base()
            ->selectRaw("{$group['id']} as group_id")
            ->selectRaw("{$group['label']} as label");

        foreach ($extras as $alias => $expression) {
            $query->selectRaw("{$expression} as {$alias}");
        }

        foreach ($this->metrics() as $alias => $expression) {
            $query->selectRaw("{$expression} as {$alias}");
        }

        return $query->groupByRaw(implode(', ', [$group['id'], $group['label'], ...array_values($extras)]));
    }

    // Ordena por fora do GROUP BY: com only_full_group_by o MySQL recusa expressões como
    // "total_value IS NULL" no ORDER BY, porque resolve o nome para a coluna e não para o alias.
    protected function rows(): QueryBuilder
    {
        return DB::query()->fromSub($this->query()->toBase(), 'report_rows');
    }

    protected function sorts(): array
    {
        $keys = ['label', ...array_keys($this->metrics())];

        return array_combine($keys, $keys);
    }

    protected function tiebreaker(): string
    {
        return 'group_id';
    }

    protected function present(object $row): array
    {
        $row = parent::present($row);
        $row['label'] ??= $this->group()['empty'];

        // No agrupamento por mês o group_id é o próprio "2026-02"; nos demais é o id do cadastro.
        if (is_numeric($row['group_id'])) {
            $row['group_id'] = (int) $row['group_id'];
        }

        return $row;
    }

    protected function groupDefinitions(): array
    {
        $month = "DATE_FORMAT({$this->dateColumn()}, '%Y-%m')";

        return [
            'city' => ['id' => 'place_address.city_id', 'label' => 'cities.name', 'empty' => 'Sem cidade', 'extras' => ['state' => 'states.letter']],
            'state' => ['id' => 'place_address.state_id', 'label' => 'states.name', 'empty' => 'Sem estado', 'extras' => ['state' => 'states.letter']],
            'client' => ['id' => 'events.client_id', 'label' => 'clients.name', 'empty' => 'Sem cliente'],
            'event_place' => ['id' => 'events.event_place_id', 'label' => 'event_places.name', 'empty' => 'Sem local', 'extras' => ['city' => 'cities.name', 'state' => 'states.letter']],
            'event_type' => ['id' => 'events.event_type_id', 'label' => 'event_types.name', 'empty' => 'Sem tipo'],
            'collaborator' => ['id' => 'events.collaborator_id', 'label' => 'collaborators.name', 'empty' => 'Sem responsável'],
            'partner' => ['id' => 'events.partner_id', 'label' => 'partners.name', 'empty' => 'Sem parceiro'],
            'status' => ['id' => 'events.event_status_id', 'label' => 'event_statuses.name', 'empty' => 'Sem status'],
            'event_capture' => ['id' => 'events.event_capture_id', 'label' => 'event_captures.name', 'empty' => 'Sem captação'],
            'how_did_you_hear' => ['id' => 'clients.how_did_you_hear_id', 'label' => 'how_did_you_hears.name', 'empty' => 'Não informado'],
            'lost_reason' => ['id' => 'events.lost_reason_id', 'label' => 'lost_reasons.name', 'empty' => 'Sem motivo informado'],
            'competitor' => ['id' => 'events.competing_company_id', 'label' => 'competing_companies.name', 'empty' => 'Sem concorrente'],
            'month' => ['id' => $month, 'label' => $month, 'empty' => 'Sem data'],
        ];
    }
}
