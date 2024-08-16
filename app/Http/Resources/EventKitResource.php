<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventKitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'value'        => $this->value,
            'themed_table' => $this->themed_table,
            'checklist'    => $this->checklist,
            'ordination'   => $this->ordination,
            'optional'     => $this->optional,
            'products'     => $this->products,
        ];
    }
}
