<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class EventNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load('user');
        $userName = $this->user ? $this->user->name : 'Nome não disponível';

        return [
            'id' => $this->id,
            'text' => $this->text,
            'user_id' => $this->user_id,
            'checklist' => $this->checklist,
            'event_id' => $this->event_id,
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'user_name' => $userName
        ];

        
    }
}
