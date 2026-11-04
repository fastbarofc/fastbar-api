<?php

namespace App\Http\Resources;

use App\Models\ClientRepresentative;
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
            'opening_date' => $this->opening_date,
            'ie'    => $this->ie,
            'im'   => $this->im,
            'user_id'   => new UserResource($this->user),
            'address'    => new AddressResource($this->address),
            'representatives' => $this->representatives->map(fn (ClientRepresentative $representative) => [
                'id'      => $representative->id,
                'name'    => $representative->name,
                'cpf'     => $representative->cpf,
                'address' => $representative->address ? new AddressResource($representative->address) : null,
            ]),
        ];
    }
}
