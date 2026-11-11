<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'internal_name' => $this->internal_name,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'type' => $this->type,
            'cnpj' => $this->cnpj,
            'municipal_registration' => $this->municipal_registration,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city ? ['id' => $this->city->id, 'name' => $this->city->name, 'state_id' => $this->city->state_id] : null),
            'tax_regime' => $this->tax_regime,
            'nf_environment' => $this->nf_environment,
            'nf_service_code' => $this->nf_service_code,
            'nf_municipal_tax_code' => $this->nf_municipal_tax_code,
            'nf_iss_rate' => $this->nf_iss_rate !== null ? (float) $this->nf_iss_rate : null,
            'nf_default_description' => $this->nf_default_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
