<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'responsible' => $this->responsible,
            'email'       => $this->email,
            'phone_1'     => $this->phone_1,
            'phone_2'     => $this->phone_2,
        ];
    }
}
