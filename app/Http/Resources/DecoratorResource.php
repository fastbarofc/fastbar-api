<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecoratorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'responsible'    => $this->responsible,
            'phone_1'        => $this->phone_1,
            'phone_2'        => $this->phone_2,
        ];
    }
}
