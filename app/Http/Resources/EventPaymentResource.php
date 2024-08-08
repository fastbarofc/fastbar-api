<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;
        return [
            'id' => $this->id,
            'value' => $this->value,
            'parthenon_bank_account' => [
                'id' => $this->parthenonBankAccount->id,
                'bank_name' => $this->parthenonBankAccount->bank_name,
                'account_number' => $this->parthenonBankAccount->account_number,
            ],
            'payment_method' => [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
            ],
            'date' => $this->date,
            'parthenon_courtesies' => $this->parthenon_courtesies,
            'comment' => $this->comment,
            'event_id' => $event->id,
        ];
    }
}
