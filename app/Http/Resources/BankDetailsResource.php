<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankDetailsResource extends JsonResource
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
            'bank_name' => $this->bank_name,
            'agency' => $this->agency,
            'account' => $this->account,
            'status' => $this->status,
            'pix_keys' => PixKeyResource::collection($this->pixKey),
        ];
    }
}
