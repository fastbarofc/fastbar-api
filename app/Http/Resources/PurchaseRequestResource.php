<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'requester_id' => $this->requester_id,
            'requester' => $this->whenLoaded('requester', function () {
                return new UserResource($this->requester);
            }),
            'sector_id' => $this->sector_id,
            'sector' => $this->whenLoaded('sector', function () {
                return new SectorResource($this->sector);
            }),
            'expected_date' => $this->expected_date ? $this->expected_date->format('Y-m-d') : null,
            'status' => $this->status,
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'factor' => $product->pivot->factor,
                        'reference' => $product->pivot->reference,
                        'quantity' => $product->pivot->quantity,
                    ];
                });
            }),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
} 