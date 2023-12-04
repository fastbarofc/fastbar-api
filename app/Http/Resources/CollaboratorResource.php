<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\this;

class CollaboratorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'         => $this->id,
            'name'       => $this->name,
            'nickname'   => $this->nickname,
            'birth_date' => $this->birth_date,
            'cpf'        => $this->cpf,
            'rg'         => $this->rg,
            'email'      => $this->email,
            'phone_1'    => $this->phone_1,
            'phone_2'    => $this->phone_2,
            'address'    => new AddressResource($this->address),
            'sector_id'  => $this->sector_id,
            'office_id'  => $this->office_id,
            'has_login'  => $this->has_login,
            'user_id'    => $this->user_id,
            'file_id'    => $this->file_id,
        ];

        return $data;
    }
}