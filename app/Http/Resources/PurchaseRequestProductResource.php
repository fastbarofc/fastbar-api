<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'purchase_request_id'   => $this->purchase_request_id,
            'purchase_request'      => PurchaseRequestResource::make($this->whenLoaded('purchaseRequest')),
            'product_id'            => $this->product_id,
            'product'               => ProductResource::make($this->whenLoaded('product')),
            'factor'                => $this->factor,
            'observation'           => $this->observation,
            'quantity'              => $this->quantity,
            'created_at'            => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
} 