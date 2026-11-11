<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'lead_id'     => $this->lead_id,
            'type'        => $this->type,
            'result'      => $this->result,
            'happened_at' => $this->happened_at?->format('Y-m-d H:i:s'),
            'notes'       => $this->notes,
            'user_id'     => $this->user_id,
            'user_name'   => $this->user?->name,
            'created_at'  => $this->created_at,
        ];
    }
}
