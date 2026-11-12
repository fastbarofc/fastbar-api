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
                'checklist'    => $this->checklist,
                'optional'     => $this->optional,
                'value'        => $this->value,
                'observation'  => $this->observation,
                'ordination'   => $this->ordination,
                'selected'     => (bool) ($this->selected ?? true),
                'type'         => $this->is_item ? 'product' : 'kit',
                'custom_item'  => (bool) $this->is_item,
            ],
            // Pasta sempre pode abrir (mostra o campo de digitar item); item digitado não tem filhos
            'children'     => $this->is_item ? [] : [[]],
            'model'        => 'event_kit'
        ];
    }
}
