<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'comment'    => $this->comment,
            'user_id'    => $this->user_id,
            'user_name'  => $this->user?->name,
            'created_at' => $this->created_at,
        ];
    }
}
