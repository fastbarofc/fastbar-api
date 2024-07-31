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
            'guests' => $event->guests,
            'courtesies' => $event->courtesies ?? 0, 
            'unit_value' => $event->unit_value,
            'unit_discount' => $event->unit_discount,
            'discount' => $event->discount,
            'increase' => 0, 
            'lease' => $event->lease,
            'unit_insurance' => $event->unit_insurance,
            'total' => $event->total_value,
            'value' => $this->value,
            'balance' => $event->total_value - $this->value,
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
            'partner_courtesies' => $this->partner_courtesies,
            'buffet_value' => $this->buffet_value,
            'total_value' => $this->total_value,
            'min_guests' => $this->min_guests,
            'duration' => $this->duration,
            'comment' => $this->comment,
            'event_id' => $event->id,
        ];
    }
}
