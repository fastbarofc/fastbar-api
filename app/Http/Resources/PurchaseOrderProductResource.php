<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'purchase_order_id'     => $this->purchase_order_id,
            'purchase_order'        => PurchaseOrderResource::make($this->whenLoaded('purchaseOrder')),
            'product_id'            => $this->product_id,
            'product'               => ProductResource::make($this->whenLoaded('product')),
            'purchase_request_id'   => $this->purchase_request_id,
            'purchase_request'      => PurchaseRequestResource::make($this->whenLoaded('purchaseRequest')),
            'factor'                => $this->factor,
            'reference'             => $this->reference,
            'quantity'              => $this->quantity,
            'price'                 => $this->price,
            'created_at'            => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
} 