<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'product_id'         => $this->product_id,
            'product'            => ProductResource::make($this->whenLoaded('product')),
            'department_id'      => $this->department_id,
            'department'         => DepartmentResource::make($this->whenLoaded('department')),
            'type'               => $this->type,
            'quantity'           => $this->quantity,
            'price'              => $this->price,
            'movementable_type'  => $this->movementable_type,
            'movementable_id'    => $this->movementable_id,
            'movementable'       => $this->whenLoaded('movementable'),
            'created_at'         => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'         => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

