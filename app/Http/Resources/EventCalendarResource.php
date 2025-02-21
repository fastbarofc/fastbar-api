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
            'date'               => Carbon::parse($this->date)->format('Y-m-d'),
            'time'               => $this->time ? Carbon::parse($this->time)->format('H:i') : null,
            'event_place'        => $this->eventPlace->name . ' (' . $this->eventPlace->address->city->name . ' - ' . $this->eventPlace->address->city->state->letter . ')',
            'guests'             => $this->event_guests + $batchGuestsCount,
            'event_status'             => $this->eventStatus->name,
            'updated_at'         => $this->updated_at,
        ];
    }
}
