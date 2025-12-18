<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentPlanResource extends JsonResource
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
            'event_id' => $this->event_id,
            'total_amount' => $this->total_amount,
            'down_payment' => $this->down_payment,
            'down_payment_due_date' => $this->down_payment_due_date?->format('Y-m-d'),
            'num_installments' => $this->num_installments,
            'installment_amount' => $this->installment_amount,
            'payment_method_id' => $this->payment_method_id,
            'due_day' => $this->due_day,
            'payment_method' => $this->whenLoaded('paymentMethod'),
            'installments' => PaymentPlanInstallmentResource::collection($this->whenLoaded('installments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
