<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'requisition_id'   => $this->requisition_id,
            'requisition'      => RequisitionResource::make($this->whenLoaded('requisition')),
            'stock_id'         => $this->stock_id,
            'stock'            => StockResource::make($this->whenLoaded('stock')),
            'registered_by_id' => $this->registered_by_id,
            'registered_by'    => UserResource::make($this->whenLoaded('registeredBy')),
            'notes'            => $this->notes,
            'items'            => DevolutionItemResource::collection($this->whenLoaded('devolutionItems')),
            'created_at'       => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
