<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PaymentMethodResource;

class ReceivableResource extends JsonResource
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
            'description' => $this->description,
            'amount' => $this->amount,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'situation' => $this->situation,
            'received_amount' => $this->received_amount,
            'notes' => $this->notes,
            'financial_operation_id' => $this->financial_operation_id,
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'parthenon_bank_account_id' => $this->parthenon_bank_account_id,
            'parthenon_bank_account' => $this->whenLoaded('parthenonBankAccount', fn () => $this->parthenonBankAccount ? [
                'id' => $this->parthenonBankAccount->id,
                'bank_name' => $this->parthenonBankAccount->bank_name,
                'account_number' => $this->parthenonBankAccount->account_number,
                'name' => $this->parthenonBankAccount->display_name,
            ] : null),
            'settled_by' => $this->whenLoaded('settledBy', fn () => $this->settledBy?->name),
            'event' => $this->whenLoaded('receivableable', function () {
                $event = $this->receivableable;
                if (!$event instanceof \App\Models\Event) {
                    return null;
                }
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => optional($event->date)->format('Y-m-d'),
                    'client' => $event->relationLoaded('client') && $event->client ? ['id' => $event->client->id, 'name' => $event->client->name] : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
