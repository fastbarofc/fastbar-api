<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'date' => Carbon::parse($this->date_time)->format('Y-m-d'),
            'event_place'        => $this->eventPlace->name . ' (' . $this->eventPlace->address->city->name . ' - ' . $this->eventPlace->address->city->state->letter . ')',
            'guests'             => $this->guests,
            'event_status'             => $this->eventStatus->name,
        ];
    }
}
