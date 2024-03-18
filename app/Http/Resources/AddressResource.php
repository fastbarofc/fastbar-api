<?php

namespace App\Http\Resources;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'         => $this->id,
            'street'     => $this->street,
            'district'   => $this->district,
            'complement' => $this->complement,
            'city_id'    => $this->city_id,
            'state_id'   => $this->state_id,
            'city_name'  => $this->city->name,
            'state_name' => $this->state->name,
            'number'     => $this->number,
            'zip_code'   => $this->zip_code,
            'reference'  => $this->reference,
        ];
    }
}