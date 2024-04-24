<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreakItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'description'    => $this->description,
            'slug'       => $this->slug,
            'value'     => $this->value,
            'unit_id'   => $this->unit_id,
            'unit_name'    => $this->unit->description, 
        ];
    }
}
