<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'unit_value' => $this->unit_value,
            'guests' => $this->guests,
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
