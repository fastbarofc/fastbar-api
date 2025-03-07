<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'status'         => $this->whenLoaded('eventStatus', fn() => $this->eventStatus->name),
            'date'           => $this->date ? Carbon::parse($this->date)->format('Y-m-d') : null,
            'time'           => $this->time ? Carbon::createFromFormat(strlen($this->time) == 5 ? 'H:i' : 'H:i:s', $this->time)->format('H:i') : null,
            'event_place'    => $this->whenLoaded('eventPlace', fn() => [
                'id'   => $this->eventPlace->id,
                'name' => $this->eventPlace->name,
            ]),
            'client'         => $this->whenLoaded('client', fn() => [
                'name'    => $this->client->name,
                'phone_1' => $this->client->phone_1,
                'phone_2' => $this->client->phone_2,
                'email'   => $this->client->email,
            ]),
            'type'           => $this->whenLoaded('eventType', fn() => $this->eventType->name),
            'collaborator'   => $this->whenLoaded('collaborator', fn() => $this->collaborator->name),
            'has_kit'        => $this->eventKits()->exists()
        ];
    }
}
