<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batchGuestsCount = $this->eventBatches->sum('guests');

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'date' => Carbon::parse($this->date_time)->format('Y-m-d'),
            'event_place'        => $this->eventPlace->name . ' (' . $this->eventPlace->address->city->name . ' - ' . $this->eventPlace->address->city->state->letter . ')',
            'guests'             => $this->event_guests + $batchGuestsCount,
            'event_status'             => $this->eventStatus->name,
            'updated_at'         => $this->updated_at,
        ];
    }
}
