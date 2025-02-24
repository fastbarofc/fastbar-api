<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\EventPlace;


class EventListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'status'         => $this->eventStatus->name ?? null,
            'date'           => $this->date ? Carbon::parse($this->date)->format('Y-m-d') : null,
            'time'           => $this->time ? Carbon::createFromFormat(strlen($this->time) == 5 ? 'H:i' : 'H:i:s', $this->time)->format('H:i') : null,
            'event_place'    => $this->eventPlace ? [
                'id'   => $this->eventPlace->id,
                'name' => $this->eventPlace->name,
            ] : null,
            'client'         => $this->client ? [
                'name'    => $this->client->name,
                'phone_1' => $this->client->phone_1,
                'phone_2' => $this->client->phone_2,
                'email'   => $this->client->email,
            ] : null,
            'type'           => $this->eventType->name ?? null,
            'collaborator'   => $this->collaborator->name ?? null,
            'event_types'    => EventType::select('id', 'name')->get(),
            'event_places'   => EventPlace::select('id', 'name')->get(),
            'formatted_event_place_info' => "{$this->eventPlace->name} - {$this->eventPlace->address->city_name} - {$this->eventPlace->address->state_letter}",
            'event_statuses' => EventStatus::select('id', 'name')->get(),
        ];
    }
}
