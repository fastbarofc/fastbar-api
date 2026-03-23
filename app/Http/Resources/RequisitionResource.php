<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'event_id'       => $this->event_id,
            'event'          => EventResource::make($this->whenLoaded('event')),
            'department_id'  => $this->department_id,
            'department'     => DepartmentResource::make($this->whenLoaded('department')),
            'requester_id'   => $this->requester_id,
            'requester'      => UserResource::make($this->whenLoaded('requester')),
            'notes'          => $this->notes,
            'has_devolution' => $this->whenLoaded('devolution', fn() => $this->devolution !== null, false),
            'items'          => RequisitionItemResource::collection($this->whenLoaded('requisitionItems')),
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
