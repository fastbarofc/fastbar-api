<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'messaging_account_id' => $this->messaging_account_id,
            'account_name'         => $this->account?->name,
            'channel'              => $this->channel,
            'contact_external_id'  => $this->contact_external_id,
            'contact_name'         => $this->contact_name,
            'contact_phone'        => $this->contact_phone,
            'contact_username'     => $this->contact_username,
            'user_id'              => $this->user_id,
            'user_name'            => $this->user?->name,
            'lead_id'              => $this->lead_id,
            'lead_stage'           => $this->lead?->stage,
            'client_id'            => $this->client_id,
            'client_name'          => $this->client?->name,
            'status'               => $this->status,
            'internal'             => $this->internal,
            'last_message_at'      => $this->last_message_at,
            'last_direction'       => $this->last_direction,
            'last_message_preview' => $this->last_message_preview,
            'unread_count'         => $this->unread_count,
            'waiting_since'        => $this->waiting_since,
            'messages'             => $this->whenLoaded('messages', fn () => MessageResource::collection($this->messages)),
        ];
    }
}
