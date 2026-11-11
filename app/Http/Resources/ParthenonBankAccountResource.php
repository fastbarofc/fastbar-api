<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParthenonBankAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'account_kind' => $this->account_kind ?: 'banco',
            'agency' => $this->agency,
            'opening_balance' => $this->opening_balance,
            'opening_balance_date' => $this->opening_balance_date?->format('Y-m-d'),
            'card_brand' => $this->card_brand,
            'card_last_digits' => $this->card_last_digits,
            'closing_day' => $this->closing_day,
            'due_day' => $this->due_day,
            'active' => $this->active === null ? true : (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

