<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventKitTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id'           => $this->id,
                'event_kit_id'       => $this->event_kit_id,
                'name'         => $this->name,
                'themed_table' => $this->themed_table,
                'optional'     => $this->optional,
                'value'        => $this->value,
            ],
            'children'     => !empty($this->children) ? [[]] : []
        ];
    }
}
