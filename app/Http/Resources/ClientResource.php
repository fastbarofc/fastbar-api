<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'nickname'   => $this->nickname,
            'birth_date' => $this->birth_date,
            'cpf'        => $this->cpf,
            'rg'         => $this->rg,
            'phone_1'    => $this->phone_1,
            'phone_2'    => $this->phone_2,
            'email'      => $this->email,
            'how_met'    => $this->how_met,
        ];
    }
}
