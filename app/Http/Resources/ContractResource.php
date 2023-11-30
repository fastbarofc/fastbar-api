<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'event_id'        => $this->event_id,
            'version'         => $this->version,
            'properties'      => $this->properties,
            'properties_diff' => $this->properties_diff,
        ];
    }
}
