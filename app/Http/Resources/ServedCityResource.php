<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServedCityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'state'         => $this->state,
            'served'        => (bool) $this->served,
            'min_guests'    => $this->min_guests,
            'user_id'       => $this->user_id,
            'user_name'     => $this->user?->name,
            'segment_rules' => $this->segment_rules ?? [],
            'notes'         => $this->notes,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
