<?php

namespace App\Http\Resources;

use App\Models\EventAddendum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventAddendumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'number' => $this->number,
            'types' => array_map(fn (string $type) => EventAddendum::TYPES[$type] ?? $type, $this->types ?? []),
            'items' => $this->document['items'] ?? [],
            'previous_total' => (float) $this->previous_total,
            'new_total' => (float) $this->new_total,
            'created_at' => $this->created_at,
            'created_by' => $this->user?->collaborator?->name ?? $this->user?->name,
        ];
    }
}
