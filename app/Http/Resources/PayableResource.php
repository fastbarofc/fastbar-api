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
            'due_date' => $this->due_date,
            'paid_at' => $this->paid_at,
            'status' => $this->status,
            'financial_operation' => new FinancialOperationResource($this->whenLoaded('financialOperation')),
            'payableable' => $this->whenLoaded('payableable'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
