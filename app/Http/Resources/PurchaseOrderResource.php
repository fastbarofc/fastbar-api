<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'price' => $this->price,
            'financial_operation_id' => $this->financial_operation_id,
            'financial_operation' => $this->whenLoaded('financialOperation', function () {
                return new FinancialOperationResource($this->financialOperation);
            }),
            'cost_center_id' => $this->cost_center_id,
            'cost_center' => $this->whenLoaded('costCenter', function () {
                return new CostCenterResource($this->costCenter);
            }),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'purchase_request_id' => $product->pivot->purchase_request_id,
                        'factor' => $product->pivot->factor,
                        'reference' => $product->pivot->reference,
                        'quantity' => $product->pivot->quantity,
                        'price' => $product->pivot->price,
                    ];
                });
            }),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
} 