<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'company_id'            => $this->company_id,
            'company'               => CompanyResource::make($this->whenLoaded('company')),
            'price'                 => $this->price,
            'financial_operation_id'=> $this->financial_operation_id,
            'financial_operation'   => FinancialOperationResource::make($this->whenLoaded('financialOperation')),
            'cost_center_id'        => $this->cost_center_id,
            'cost_center'           => CostCenterResource::make($this->whenLoaded('costCenter')),
            'stock_id'              => $this->stock_id,
            'stock'                 => StockResource::make($this->whenLoaded('stock')),
            'status'                => $this->status,
            'products'              => PurchaseOrderProductResource::collection($this->whenLoaded('purchaseOrderProducts')),
            'created_by'            => $this->created_by,
            'creator'               => UserResource::make($this->whenLoaded('creator')),
            'updated_by'            => $this->updated_by,
            'last_editor'           => UserResource::make($this->whenLoaded('lastEditor')),
            'created_at'            => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
