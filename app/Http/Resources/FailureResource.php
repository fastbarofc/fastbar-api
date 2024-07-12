<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FailureResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'percentage' => $this->percentage,
        ];
    }
}
