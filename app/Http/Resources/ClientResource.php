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
            'how_met'    => $this->how_met,
            'type'       => $this->type,
            'email'     => $this->email,
            'phone_1'    => $this->phone_1,
            'phone_2'   => $this->phone_2,
            'name'      => $this->name,
            'nickname' => $this->nickname,
            'birth_date' => $this->birth_date,
            'cpf'      => $this->cpf,
            'rg'      => $this->rg,
            'company_name' => $this->company_name,
            'fantasy_name' => $this->fantasy_name,
            'cnpj'     => $this->cnpj,
            'ie'    => $this->ie,
            'im'   => $this->im,
            'address'    => new AddressResource($this->address),
        ];
    }
}
