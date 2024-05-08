<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'kit_id'       => $this->kit_id,
            'name'         => $this->name,
            'themed_table' => $this->themed_table,
            'optional'     => $this->optional,
            'value'        => $this->value,
            'children'     => $this->children
        ];
    }
}
