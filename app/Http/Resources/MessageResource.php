<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'direction'  => $this->direction,
            'type'       => $this->type,
            'body'       => $this->body,
            'media_url'  => $this->media_url,
            'media_mime' => $this->media_mime,
            'media_name' => $this->media_name,
            'status'     => $this->status,
            'error'      => $this->error,
            'user_name'  => $this->user?->name,
            'sent_at'    => $this->sent_at,
        ];
    }
}
