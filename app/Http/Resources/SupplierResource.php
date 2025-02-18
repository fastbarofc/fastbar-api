<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'corporate_name' => $this->corporate_name,
            'fantasy_name' => $this->fantasy_name,
            'cnpj' => $this->cnpj,
            'state_registration' => $this->state_registration,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'account_holder' => $this->account_holder,
            'observations' => $this->observations,
            'user_id' => new UserResource($this->user_id),
            'address' => new AddressResource($this->address),
            'bank_details' => $this->bankDetails->first() ? new BankDetailResource($this->bankDetails->first()) : null,
    'pix_keys' => $this->pixKeys->first() ? new PixKeyResource($this->pixKeys->first()) : null,
        ];
    }
}
