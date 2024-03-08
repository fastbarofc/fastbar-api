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
            'value_unit'         => $this->value_unit,
            'discount'       => $this->discount,
            'discount_unit'       => $this->discount_unit,
            'contract_body'      => $this->contract_body,
            'courtesy_parthenon' => $this->courtesy_parthenon,
            'courtesy_partner'   => $this->courtesy_partner,
            'courtesies'         => $this->courtesies,
        ];
    }
}
