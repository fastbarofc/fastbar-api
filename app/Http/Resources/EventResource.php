<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'client_id'          => $this->client_id,
            'client'        => $this->client,
            'collaborator_id'    => $this->collaborator_id,
            'collaborator'    => $this->collaborator,
            'event_type_id'      => $this->event_type_id,
            'type'               => $this->eventType,
            'event_place_id'     => $this->event_place_id,
            'event_place'        => $this->eventPlace,
            'event_status_id'    => $this->event_status_id,
            'status'             => $this->eventStatus,
            'lease'              => $this->lease,
            'guests'             => $this->guests,
            'min_guests'         => $this->min_guests,
            'duration'           => $this->duration,
            'date_time'          => $this->date_time->format('Y-m-d\TH:i'),
            'duration_after'    => $this->duration_after,
            'unit_value'         => $this->unit_value,
            'discount'       => $this->discount,
            'unit_discount'       => $this->unit_discount,
            'contract_body'      => $this->contract_body,
            'parthenon_courtesies' => $this->parthenon_courtesies,
            'partner_courtesies'   => $this->partner_courtesies,
            'courtesies'         => $this->courtesies,
            'unit_insurance'     => $this->unit_insurance,
            'buffet_value'       => $this->buffet_value,
            'total_value'        => $this->total_value,
            'partner_id'         => $this->partner_id,
            'decorator_id' => $this->decorator_id,
            'event_capture_id' => $this->event_capture_id,
            'ceremonial_id' => $this->ceremonial_id,
            'legal_representatives' => $this->legal_representatives,
            'event_gifts'        => $this->eventGifts,
            'materials'          => $this->materials->map(function ($material) {
                return [
                    'id' => $material->id,
                    'description' => $material->description,
                    'quantity' => $material->pivot->quantity,
                    'selected' => $material->pivot->selected,
                ];
            }),
        ];
    }
}
