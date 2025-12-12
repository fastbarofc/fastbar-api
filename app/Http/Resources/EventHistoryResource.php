<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventHistoryResource extends JsonResource
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
            'action' => $this->action,
            'description' => $this->description,
            'changes' => $this->changes,
            'old_data' => $this->old_data,
            'new_data' => $this->new_data,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'collaborator' => [
                    'id' => $this->user?->collaborator?->id,
                    'name' => $this->user?->collaborator?->name,
                    'sector' => [
                        'id' => $this->user?->collaborator?->sector?->id,
                        'name' => $this->user?->collaborator?->sector?->name,
                    ],
                    'sector_office' => [
                        'id' => $this->user?->collaborator?->sectorOffice?->id,
                        'name' => $this->user?->collaborator?->sectorOffice?->name,
                    ],
                ],
            ],
        ];
    }
}
