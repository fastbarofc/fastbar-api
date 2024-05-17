<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'slug'=> $this->slug,
            'file_id'=> $this->file_id,
            'product_group_id'=> $this->product_group_id,
            'barcode'=> $this->barcode,
            'unit_id'=> $this->unit_id,
            'weight'=> $this->weight,
            'manufacturer'=> $this->manufacturer,
            'value'=> $this->value,
            'supplier'=> $this->supplier,
            'marketable'=> $this->marketable,
            'average_consumption'=> $this->average_consumption,
            'display_checklist'=> $this->display_checklist,
            'own_production'=> $this->own_production,
            'recipe_yield'=> $this->recipe_yield,
        ];
    }
}
