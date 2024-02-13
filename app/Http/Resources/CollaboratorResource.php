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
            'id'             => $this->id,
            'name'           => $this->name,
            'nickname'       => $this->nickname,
            'birth_date'     => $this->birth_date,
            'cpf'            => $this->cpf,
            'rg'             => $this->rg,
            'email'          => $this->email,
            'phone_1'        => $this->phone_1,
            'phone_2'        => $this->phone_2,
            'address'        => new AddressResource($this->address),
            'sector'         => $this->sector,
            'sector_office'  => $this->sectorOffice,
            'has_login'      => $this->has_login,
            'active'         => $this->active,
            'user_id'        => $this->user_id,
            'photo'          => $this->photo,
            'qualifications' => $this->qualifications->map(function ($qualification) {
                return [
                    'id'          => $qualification->id,
                    'description' => $qualification->description,
                    'points'      => $qualification->pivot->points ?? '0',
                ];
            }),
        ];

        return $data;
    }
}
