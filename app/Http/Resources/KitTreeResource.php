<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'id'           => $this->id,
                'kit_id'       => $this->kit_id,
                'name'         => $this->name,
                'themed_table' => $this->themed_table,
                'checklist'    => $this->checklist,
                'optional'     => $this->optional,
                'value'        => $this->value,        
                'ordination'   => $this->ordination,
            ],
            'children'     => !empty($this->has_children) ? [[]] : [],
            'model'        => 'kit'
        ];
    }
}
