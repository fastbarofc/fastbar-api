<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\FinancialOperationResource;

class PayableResource extends JsonResource
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
            'event_id' => $this->event_id,
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'date' => optional($this->event->date)->format('Y-m-d'),
                'client' => $this->event->relationLoaded('client') && $this->event->client ? ['id' => $this->event->client->id, 'name' => $this->event->client->name] : null,
            ] : null),
            'category' => $this->category,
            'paid_amount' => $this->paid_amount,
            'approval_status' => $this->approval_status,
            'situation' => $this->situation,
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy?->name),
            'approved_at' => $this->approved_at,
            'cost_center_id' => $this->cost_center_id,
            'cost_center' => $this->whenLoaded('costCenter'),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? ['id' => $this->supplier->id, 'name' => $this->supplier->fantasy_name ?: $this->supplier->corporate_name] : null),
            'partner_id' => $this->partner_id,
            'partner' => $this->whenLoaded('partner', fn () => $this->partner ? ['id' => $this->partner->id, 'name' => $this->partner->name] : null),
            'collaborator_id' => $this->collaborator_id,
            'collaborator' => $this->whenLoaded('collaborator', fn () => $this->collaborator ? ['id' => $this->collaborator->id, 'name' => $this->collaborator->name] : null),
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod ? ['id' => $this->paymentMethod->id, 'name' => $this->paymentMethod->name] : null),
            'parthenon_bank_account_id' => $this->parthenon_bank_account_id,
            'parthenon_bank_account' => $this->whenLoaded('parthenonBankAccount', fn () => $this->parthenonBankAccount ? [
                'id' => $this->parthenonBankAccount->id,
                'bank_name' => $this->parthenonBankAccount->bank_name,
                'account_number' => $this->parthenonBankAccount->account_number,
                'name' => $this->parthenonBankAccount->display_name,
            ] : null),
            'card_account_id' => $this->card_account_id,
            'card_account' => $this->whenLoaded('cardAccount', fn () => $this->cardAccount ? [
                'id' => $this->cardAccount->id,
                'name' => $this->cardAccount->display_name,
            ] : null),
            'notes' => $this->notes,
            'financial_operation_id' => $this->financial_operation_id,
            'financial_operation' => new FinancialOperationResource($this->whenLoaded('financialOperation')),
            'payableable' => $this->whenLoaded('payableable'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
