<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;

class EventsReport extends Report
{
    protected const CASTS = [
        'id' => 'int',
        'guests' => 'int',
        'total_value' => 'float',
        'paid' => 'float',
        'balance' => 'float',
        'events' => 'int',
        'average_ticket' => 'float',
    ];

    protected function query(): Builder
    {
        return $this->events()
            ->select([
                'events.id',
                'events.name',
                'events.date',
                'events.time',
                'events.closed_at',
                'events.created_at',
                'events.event_guests as guests',
                'events.total_value',
                'events.status_reason',
                'clients.name as client',
                'cities.name as city',
                'states.letter as state',
                'event_places.name as event_place',
                'event_types.name as event_type',
                'event_statuses.name as status',
                'collaborators.name as collaborator',
                'partners.name as partner',
                'event_captures.name as event_capture',
                'how_did_you_hears.name as how_did_you_hear',
                'lost_reasons.name as lost_reason',
                'competing_companies.name as competitor',
            ])
            ->selectRaw(self::PAID . ' as paid')
            ->selectRaw(self::BALANCE . ' as balance');
    }

    protected function sorts(): array
    {
        return [
            'id' => 'events.id',
            'date' => 'events.date',
            'name' => 'events.name',
            'client' => 'clients.name',
            'city' => 'cities.name',
            'state' => 'states.letter',
            'event_place' => 'event_places.name',
            'event_type' => 'event_types.name',
            'status' => 'event_statuses.name',
            'collaborator' => 'collaborators.name',
            'partner' => 'partners.name',
            'event_capture' => 'event_captures.name',
            'how_did_you_hear' => 'how_did_you_hears.name',
            'lost_reason' => 'lost_reasons.name',
            'competitor' => 'competing_companies.name',
            'guests' => 'events.event_guests',
            'total_value' => 'events.total_value',
            'paid' => self::PAID,
            'balance' => self::BALANCE,
            'closed_at' => 'events.closed_at',
            'created_at' => 'events.created_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['date', 'asc'];
    }

    protected function totals(): array
    {
        return $this->aggregate($this->events(), $this->revenueMetrics());
    }
}
