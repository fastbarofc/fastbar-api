<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialOperationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'account' => $this->account,
            'nature' => $this->nature,
            'type' => $this->type,
            'dre_section' => $this->dre_section,
            'effective_dre_section' => $this->effective_dre_section,
            'dre_order' => $this->dre_order,
            'active' => $this->active === null ? true : (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
