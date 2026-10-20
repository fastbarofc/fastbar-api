<?php

namespace App\Services\Reports;

use App\Models\Event;
use App\Models\EventPlace;
use App\Models\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class Report
{
    public const PERMISSION = 'relatorios-financeiro';

    protected const PAID = 'COALESCE(payments.paid, 0)';

    // Sem valor total definido não há saldo a calcular: fica nulo em vez de negativo.
    protected const BALANCE = 'events.total_value - COALESCE(payments.paid, 0)';

    protected const DATE_FIELDS = [
        'date' => 'events.date',
        'closed_at' => 'events.closed_at',
        'created_at' => 'events.created_at',
    ];

    protected const DEFAULT_DATE_FIELD = 'date';

    protected const ID_FILTERS = [
        'status_ids' => 'events.event_status_id',
        'state_ids' => 'place_address.state_id',
        'city_ids' => 'place_address.city_id',
        'client_ids' => 'events.client_id',
        'event_place_ids' => 'events.event_place_id',
        'event_type_ids' => 'events.event_type_id',
        'collaborator_ids' => 'events.collaborator_id',
        'partner_ids' => 'events.partner_id',
        'event_capture_ids' => 'events.event_capture_id',
        'how_did_you_hear_ids' => 'clients.how_did_you_hear_id',
        'lost_reason_ids' => 'events.lost_reason_id',
        'competitor_ids' => 'events.competing_company_id',
    ];

    protected const CASTS = [];

    private ?array $summary = null;

    private array $statusIds = [];

    public function __construct(protected array $filters = [])
    {
    }

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

    protected function cast(array $values): array
    {
        foreach (static::CASTS as $key => $type) {
            if (array_key_exists($key, $values) && $values[$key] !== null) {
                $values[$key] = match ($type) {
                    'int' => (int) $values[$key],
                    'rate' => round((float) $values[$key], 4),
                    default => round((float) $values[$key], 2),
                };
            }
        }

        return $values;
    }

    protected function aggregate(Builder $query, array $metrics): array
    {
        foreach ($metrics as $alias => $expression) {
            $query->selectRaw("{$expression} as {$alias}");
        }

        return $this->cast((array) $query->toBase()->first());
    }

    protected function revenueMetrics(): array
    {
        return [
            'events' => 'COUNT(events.id)',
            'guests' => 'SUM(events.event_guests)',
            'total_value' => 'SUM(events.total_value)',
            // Eventos ainda sem valor definido não puxam o ticket médio para baixo.
            'average_ticket' => 'SUM(events.total_value) / NULLIF(SUM(events.total_value > 0), 0)',
            'paid' => 'SUM(' . self::PAID . ')',
            'balance' => 'SUM(' . self::BALANCE . ')',
        ];
    }

    protected function statusIds(array $names): string
    {
        $ids = $this->statusIds[implode('|', $names)] ??= EventStatus::whereIn('name', $names)->pluck('id')->map(fn ($id) => (int) $id);

        return $ids->isEmpty() ? 'NULL' : $ids->implode(', ');
    }

    protected function events(bool $withPeriod = true): Builder
    {
        $placeAddresses = DB::table('addresses')
            ->selectRaw('addressable_id as event_place_id, MAX(id) as address_id')
            ->where('addressable_type', EventPlace::class)
            ->whereNull('deleted_at')
            ->groupBy('addressable_id');

        $payments = DB::table('event_payments')
            ->selectRaw('event_id, SUM(value) as paid')
            ->whereNull('deleted_at')
            ->groupBy('event_id');

        $query = Event::query()
            ->where('events.is_draft', false)
            ->leftJoin('event_places', 'event_places.id', '=', 'events.event_place_id')
            ->leftJoinSub($placeAddresses, 'place_addresses', 'place_addresses.event_place_id', '=', 'events.event_place_id')
            ->leftJoin('addresses as place_address', 'place_address.id', '=', 'place_addresses.address_id')
            ->leftJoin('cities', 'cities.id', '=', 'place_address.city_id')
            ->leftJoin('states', 'states.id', '=', 'place_address.state_id')
            ->leftJoin('clients', 'clients.id', '=', 'events.client_id')
            ->leftJoin('event_statuses', 'event_statuses.id', '=', 'events.event_status_id')
            ->leftJoin('event_types', 'event_types.id', '=', 'events.event_type_id')
            ->leftJoin('collaborators', 'collaborators.id', '=', 'events.collaborator_id')
            ->leftJoin('partners', 'partners.id', '=', 'events.partner_id')
            ->leftJoin('event_captures', 'event_captures.id', '=', 'events.event_capture_id')
            ->leftJoin('how_did_you_hears', 'how_did_you_hears.id', '=', 'clients.how_did_you_hear_id')
            ->leftJoin('lost_reasons', 'lost_reasons.id', '=', 'events.lost_reason_id')
            ->leftJoin('competing_companies', 'competing_companies.id', '=', 'events.competing_company_id')
            ->leftJoinSub($payments, 'payments', 'payments.event_id', '=', 'events.id');

        $this->applyFilters($query);

        if ($withPeriod) {
            $this->applyPeriod($query, $this->dateColumn());
        }

        return $query;
    }

    protected function applyFilters(Builder $query): void
    {
        foreach (static::ID_FILTERS as $filter => $column) {
            if (!empty($this->filters[$filter])) {
                $query->whereIn($column, $this->filters[$filter]);
            }
        }

        $ranges = [
            'min_value' => ['events.total_value', '>='],
            'max_value' => ['events.total_value', '<='],
            'min_guests' => ['events.event_guests', '>='],
            'max_guests' => ['events.event_guests', '<='],
        ];

        foreach ($ranges as $filter => [$column, $operator]) {
            if (isset($this->filters[$filter])) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $query) use ($search) {
                $query->where('events.name', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('events.id', $search);
                }
            });
        }

        match ($this->filters['financial_situation'] ?? null) {
            'paid_off' => $query->where('events.total_value', '>', 0)->whereRaw(self::BALANCE . ' <= 0'),
            'with_balance' => $query->whereRaw(self::BALANCE . ' > 0'),
            'no_payment' => $query->whereNull('payments.paid'),
            default => null,
        };
    }

    protected function applyPeriod(Builder $query, string $column): void
    {
        if (!empty($this->filters['period_start'])) {
            $query->where($column, '>=', $this->filters['period_start']);
        }

        if (!empty($this->filters['period_end'])) {
            $query->where($column, '<=', $this->filters['period_end'] . ' 23:59:59');
        }
    }

    protected function dateColumn(): string
    {
        return static::DATE_FIELDS[$this->filters['date_field'] ?? static::DEFAULT_DATE_FIELD]
            ?? static::DATE_FIELDS[static::DEFAULT_DATE_FIELD];
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

    protected function share(float $value, float $total): float
    {
        return $total > 0 ? round($value / $total, 4) : 0.0;
    }
}
