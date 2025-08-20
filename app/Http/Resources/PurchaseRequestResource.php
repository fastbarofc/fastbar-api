<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_id'   => $this->company_id,
            'company'      => CompanyResource::make($this->whenLoaded('company')),
            'requester_id' => $this->requester_id,
            'requester'    => UserResource::make($this->whenLoaded('requester')),
            'sector_id'    => $this->sector_id,
            'sector'       => SectorResource::make($this->whenLoaded('sector')),
            'expected_date'=> optional($this->expected_date)->format('Y-m-d'),
            'status'       => $this->status,
            'products'     => PurchaseRequestProductResource::collection($this->whenLoaded('purchaseRequestProducts')),
            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
} 