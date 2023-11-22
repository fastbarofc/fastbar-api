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
            'description'=> $this->description,
            'group'=> $this->group,
            'barcode'=> $this->barcode,
            'unit'=> $this->unit,
            'weight'=> $this->weight,
            'manufatturer'=> $this->manufatturer,
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
