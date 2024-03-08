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
            'collaborator_id'    => $this->collaborator_id,
            'event_type_id'      => $this->event_type_id,
            'event_place_id'     => $this->event_place_id,
            'event_status_id'    => $this->event_status_id,
            'lease'              => $this->lease,
            'guests'             => $this->guests,
            'min_guests'         => $this->min_guests,
            'duration'           => $this->duration,
            'date_time'          => $this->date_time,
            'durantion_after'    => $this->durantion_after,
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
        ];
    }
}
