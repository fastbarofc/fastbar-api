<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductSubgroupResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'slug'=> $this->slug,
            'file_id'=> $this->file_id,
            'file_path'      => $this->file ? asset('storage/' . $this->file->path) : null,
            'product_group_id'    => $this->product_group_id,
            'product_group'       => ProductGroupResource::make($this->whenLoaded('productGroup')),
            'product_subgroup_id' => $this->product_subgroup_id,
            'product_subgroup'    => ProductSubgroupResource::make($this->whenLoaded('productSubgroup')),
            'barcode'=> $this->barcode,
            'unit_id'=> $this->unit_id,
            'unit'=> UnitResource::make($this->whenLoaded('unit')),
            'weight'=> $this->weight,
            'volume'=> $this->volume,
            'value'=> $this->value,
            'supplier'=> $this->supplier,
            'marketable'=> $this->marketable,
            'consumption_per_guest'=> $this->consumption_per_guest,
            'display_checklist'=> $this->display_checklist,
            'own_production'=> $this->own_production,
            'composition_item'=> $this->composition_item,
            'recipe_yield'=> $this->recipe_yield,
            'composition_count' => $this->whenCounted('components'),
            'composition' => $this->whenLoaded('components', fn () => $this->components->map(fn ($component) => [
                'product_id' => $component->id,
                'name' => $component->name,
                'unit' => $component->unit?->description,
                'quantity' => (float) $component->pivot->quantity,
            ])),
            'used_in' => $this->whenLoaded('composedProducts', fn () => $this->composedProducts->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])),
        ];
    }
}
