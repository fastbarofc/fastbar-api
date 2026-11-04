<?php

namespace App\Services\Charts;

use App\Models\EventStatus;
use App\Services\Reports\RevenueReport;

class FinancialCharts extends Charts
{
    public const PERMISSION = 'graficos-financeiro';

    public const DIMENSIONS = ['event_type', 'city', 'event_place', 'partner', 'client', 'collaborator'];

    protected const DATE_FIELDS = ['date' => 'events.date'];

    protected const CASTS = [
        'events' => 'int',
        'guests' => 'int',
        'payments' => 'int',
        'total_value' => 'float',
        'average_ticket' => 'float',
        'value_per_guest' => 'float',
        'paid' => 'float',
        'balance' => 'float',
        'total' => 'float',
        'share' => 'rate',
    ];

    private const GUEST_BANDS = [
        [100, 'Até 100'],
        [200, '101 a 200'],
        [300, '201 a 300'],
        [500, '301 a 500'],
        [null, 'Mais de 500'],
    ];

    public function __construct(array $filters = [])
    {
        $confirmed = EventStatus::whereIn('name', EventStatus::CONFIRMED)->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Faturamento é sempre dos eventos confirmados, pela data do evento.
        parent::__construct(['status_ids' => $confirmed ?: [0], 'date_field' => 'date'] + $filters);
    }

    public function data(): array
    {
        return [
            'summary' => $this->summary(),
            'by_month' => $this->byMonth(),
            'breakdowns' => collect(self::DIMENSIONS)->mapWithKeys(fn (string $dimension) => [$dimension => $this->breakdown($dimension)]),
            'payment_methods' => $this->paymentMethods(),
            'guest_bands' => $this->guestBands(),
        ];
    }

    private function summary(): array
    {
        return $this->aggregate($this->events(), $this->revenueMetrics() + [
            'value_per_guest' => 'SUM(events.total_value) / NULLIF(SUM(CASE WHEN events.total_value > 0 THEN events.event_guests END), 0)',
        ]);
    }

    private function byMonth(): array
    {
        $rows = $this->revenue('month')
            ->all()
            ->whereNotNull('group_id')
            ->map(fn (array $row) => ['month' => $row['group_id']] + $row);

        return $this->monthly($rows, ['events', 'total_value', 'paid', 'balance']);
    }

    private function breakdown(string $dimension): array
    {
        $report = $this->revenue($dimension);

        return $this->top(
            $report->all()->filter(fn (array $row) => ($row['total_value'] ?? 0) > 0),
            ['events', 'guests', 'total_value', 'paid', 'balance'],
            fn (array $others) => $others + [
                'average_ticket' => null,
                'share' => $this->share($others['total_value'], (float) $report->summary()['total_value']),
            ],
        );
    }

    private function revenue(string $groupBy): RevenueReport
    {
        return new RevenueReport(['group_by' => $groupBy] + $this->filters);
    }

    // Recebimentos dos eventos do período, para a soma bater com o "Recebido" do resumo.
    private function paymentMethods(): array
    {
        $rows = $this->events()
            ->toBase()
            ->join('event_payments', 'event_payments.event_id', '=', 'events.id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'event_payments.payment_method_id')
            ->whereNull('event_payments.deleted_at')
            ->selectRaw("COALESCE(payment_methods.name, 'Não informado') as label")
            ->selectRaw('COUNT(event_payments.id) as payments')
            ->selectRaw('SUM(event_payments.value) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $total = (float) $rows->sum('total');

        return $rows
            ->map(fn (object $row) => $this->cast((array) $row + ['share' => $this->share((float) $row->total, $total)]))
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function guestBands(): array
    {
        $cases = collect(self::GUEST_BANDS)
            ->map(fn (array $band, int $index) => $band[0] ? "WHEN events.event_guests <= {$band[0]} THEN {$index}" : "ELSE {$index}")
            ->implode(' ');
        $band = "CASE {$cases} END";

        $rows = $this->events()
            ->toBase()
            ->where('events.event_guests', '>', 0)
            ->selectRaw("{$band} as band")
            ->selectRaw('COUNT(events.id) as events')
            ->selectRaw('SUM(events.total_value) as total_value')
            ->selectRaw('SUM(events.total_value) / NULLIF(SUM(events.total_value > 0), 0) as average_ticket')
            ->selectRaw('SUM(events.total_value) / NULLIF(SUM(CASE WHEN events.total_value > 0 THEN events.event_guests END), 0) as value_per_guest')
            ->groupByRaw($band)
            ->get()
            ->keyBy('band');

        return collect(self::GUEST_BANDS)->map(fn (array $band, int $index) => $this->cast([
            'label' => $band[1],
            'events' => $rows[$index]->events ?? 0,
            'total_value' => $rows[$index]->total_value ?? 0,
            'average_ticket' => $rows[$index]->average_ticket ?? null,
            'value_per_guest' => $rows[$index]->value_per_guest ?? null,
        ]))->all();
    }
}
