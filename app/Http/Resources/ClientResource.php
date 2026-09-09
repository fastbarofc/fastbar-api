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
            'how_did_you_hear_id' => $this->how_did_you_hear_id,
            'type'       => $this->type,
            'email'     => $this->email,
            'phone_1'    => $this->phone_1,
            'phone_2'   => $this->phone_2,
            'name'      => $this->name,
            'nickname' => $this->nickname,
            'birth_date' => $this->birth_date,
            'cpf'      => $this->cpf,
            'rg'      => $this->rg,
            'cnpj'     => $this->cnpj,
            'representative_name' => $this->representative_name,
            'representative_cpf'  => $this->representative_cpf,
            'opening_date' => $this->opening_date,
            'ie'    => $this->ie,
            'im'   => $this->im,
            'user_id'   => new UserResource($this->user),
            'address'    => new AddressResource($this->address),
            'representative_address' => $this->representativeAddress
                ? new AddressResource($this->representativeAddress)
                : null,
        ];
    }
}
