<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'unit_id'     => $this->unit_id,
            'quantity'    => $this->quantity,
            'unit_value'  => $this->unit_value,
            'event_id'    => $this->event_id,
            'unit_name'    => $this->unit->description,
        ];
    }
}
