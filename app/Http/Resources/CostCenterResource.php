<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CostCenterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'account' => $this->account,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
