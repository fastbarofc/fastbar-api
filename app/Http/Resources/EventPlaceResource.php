<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventPlaceResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        $data = [
            'id'                => $this->id,  
            'name'              => $this->name,  
            'responsible'       => $this->responsible,  
            'description'       => $this->description,  
            'phone_1'           => $this->phone_1,
            'phone_2'           => $this->phone_2,
            'email'             => $this->email,
            'address'           => new AddressResource($this->address),
        ];

        return $data;
    }
}
