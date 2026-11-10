<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['users:id,name', 'event:id,name', 'creator:id,name']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'location' => $this->location,
            'event_id' => $this->event_id,
            'event_name' => $this->event?->name,
            'users' => $this->users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->values(),
            'user_ids' => $this->users->pluck('id')->values(),
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
