<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class Report extends EventDataset
{
    public const PERMISSION = 'relatorios-financeiro';

    private ?array $summary = null;

    abstract protected function query(): Builder;

    abstract protected function sorts(): array;

    abstract protected function defaultSort(): array;

    abstract protected function totals(): array;

    public function summary(): array
    {
        return $this->summary ??= $this->totals();
    }

    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return $this->sorted($this->rows())
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (object $row) => $this->present($row));
    }

    public function count(): int
    {
        return $this->rows()->getCountForPagination();
    }

    public function all(): Collection
    {
        return $this->sorted($this->rows())
            ->get()
            ->map(fn (object $row) => $this->present($row));
    }

    protected function rows(): QueryBuilder
    {
        return $this->query()->toBase();
    }

    protected function present(object $row): array
    {
        return $this->cast((array) $row);
    }

    protected function sorted(QueryBuilder $query): QueryBuilder
    {
        $sorts = $this->sorts();
        $key = $this->filters['order_by'] ?? '';

        if (isset($sorts[$key])) {
            $direction = ($this->filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        } else {
            [$key, $direction] = $this->defaultSort();
        }

        // Linhas sem valor (ex.: evento sem local) ficam no fim nos dois sentidos.
        return $query
            ->orderByRaw("{$sorts[$key]} IS NULL")
            ->orderByRaw("{$sorts[$key]} {$direction}")
            ->orderByRaw($this->tiebreaker());
    }

    protected function tiebreaker(): string
    {
        return 'events.id';
    }
}
