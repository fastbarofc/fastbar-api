<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;

class PaymentsReport extends Report
{
    protected const CASTS = [
        'id' => 'int',
        'event_id' => 'int',
        'value' => 'float',
    ];

    private const PAYMENT_FILTERS = [
        'payment_method_ids' => 'event_payments.payment_method_id',
        'bank_account_ids' => 'event_payments.parthenon_bank_account_id',
    ];

    private const BANK_ACCOUNT = "CONCAT_WS(' - ', parthenon_bank_accounts.bank_name, parthenon_bank_accounts.account_number)";

    protected function payments(): Builder
    {
        $query = $this->events(withPeriod: false)
            ->join('event_payments', 'event_payments.event_id', '=', 'events.id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'event_payments.payment_method_id')
            ->leftJoin('parthenon_bank_accounts', 'parthenon_bank_accounts.id', '=', 'event_payments.parthenon_bank_account_id')
            ->whereNull('event_payments.deleted_at');

        foreach (self::PAYMENT_FILTERS as $filter => $column) {
            if (!empty($this->filters[$filter])) {
                $query->whereIn($column, $this->filters[$filter]);
            }
        }

        $this->applyPeriod($query, 'event_payments.date');

        return $query;
    }

    protected function query(): Builder
    {
        return $this->payments()
            ->select([
                'event_payments.id',
                'event_payments.date',
                'event_payments.value',
                'event_payments.comment',
                'events.id as event_id',
                'events.name as event_name',
                'events.date as event_date',
                'event_statuses.name as status',
                'clients.name as client',
                'cities.name as city',
                'states.letter as state',
                'payment_methods.name as payment_method',
            ])
            ->selectRaw(self::BANK_ACCOUNT . ' as bank_account');
    }

    protected function sorts(): array
    {
        return [
            'date' => 'event_payments.date',
            'value' => 'event_payments.value',
            'event_id' => 'events.id',
            'event_name' => 'events.name',
            'event_date' => 'events.date',
            'status' => 'event_statuses.name',
            'client' => 'clients.name',
            'city' => 'cities.name',
            'state' => 'states.letter',
            'payment_method' => 'payment_methods.name',
            'bank_account' => self::BANK_ACCOUNT,
        ];
    }

    protected function defaultSort(): array
    {
        return ['date', 'desc'];
    }

    protected function tiebreaker(): string
    {
        return 'event_payments.id';
    }

    protected function totals(): array
    {
        $totals = $this->aggregate($this->payments(), [
            'total' => 'SUM(event_payments.value)',
            'payments' => 'COUNT(event_payments.id)',
            'events' => 'COUNT(DISTINCT events.id)',
        ]);

        $total = (float) ($totals['total'] ?? 0);
        $payments = (int) ($totals['payments'] ?? 0);

        $byMethod = $this->payments()
            ->toBase()
            ->selectRaw("COALESCE(payment_methods.name, 'Não informado') as label")
            ->selectRaw('COUNT(event_payments.id) as payments')
            ->selectRaw('SUM(event_payments.value) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $row) => [
                'label' => $row->label,
                'payments' => (int) $row->payments,
                'total' => round((float) $row->total, 2),
                'share' => $this->share((float) $row->total, $total),
            ]);

        return [
            'total' => round($total, 2),
            'payments' => $payments,
            'events' => (int) ($totals['events'] ?? 0),
            'average' => $payments ? round($total / $payments, 2) : 0,
            'by_method' => $byMethod,
        ];
    }
}
