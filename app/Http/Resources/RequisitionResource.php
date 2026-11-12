<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'event_id'              => $this->event_id,
            'event'                 => $this->whenLoaded('event', fn () => $this->event ? [
                'id'   => $this->event->id,
                'name' => $this->event->name,
                'date' => optional($this->event->date)->format('Y-m-d'),
            ] : null),
            'stock_id'              => $this->stock_id,
            'stock'                 => StockResource::make($this->whenLoaded('stock')),
            'destination_stock_id'  => $this->destination_stock_id,
            'destination_stock'     => StockResource::make($this->whenLoaded('destinationStock')),
            'requester_id'          => $this->requester_id,
            'requester'             => UserResource::make($this->whenLoaded('requester')),
            'notes'                 => $this->notes,
            'status'                => $this->status ?? 'open',
            'items'                 => RequisitionItemResource::collection($this->whenLoaded('requisitionItems')),
            'devolutions'           => DevolutionResource::collection($this->whenLoaded('devolutions')),
            'created_at'            => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
