<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'department_id' => $this->department_id,
            'current_quantity' => $this->current_quantity,
            'weighted_average_price' => $this->weighted_average_price,
            'total_value' => $this->total_value,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'min_limit_reached' => $this->min_quantity !== null && $this->current_quantity <= $this->min_quantity,
            'max_limit_reached' => $this->max_quantity !== null && $this->current_quantity >= $this->max_quantity,
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'unit' => $this->product->unit?->description,
                'product_group' => $this->product->productGroup?->description,
            ]),
            'department' => $this->whenLoaded('department', fn() => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
