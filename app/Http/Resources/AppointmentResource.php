<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['users:id,name', 'event:id,name', 'creator:id,name', 'client:id,name', 'partner:id,name', 'supplier:id,corporate_name,fantasy_name', 'participants', 'recordings']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'location' => $this->location,
            'meeting_url' => $this->meeting_url,
            'google_event_id' => $this->google_event_id,
            'summary' => $this->summary,
            'summary_status' => $this->summary_status,
            'summary_error' => $this->summary_error,
            'client_id' => $this->client_id,
            'client_name' => $this->client?->name,
            'partner_id' => $this->partner_id,
            'partner_name' => $this->partner?->name,
            'supplier_id' => $this->supplier_id,
            'participants' => \App\Models\AppointmentParticipant::describe($this->participants),
            'supplier_name' => $this->supplier ? ($this->supplier->fantasy_name ?: $this->supplier->corporate_name) : null,
            'recordings' => $this->recordings
                ->where('status', \App\Models\AppointmentRecording::STATUS_DONE)
                ->map(fn ($recording) => [
                    'id' => $recording->id,
                    'duration_seconds' => $recording->duration_seconds,
                    'size' => $recording->size,
                    'created_at' => $recording->created_at?->format('d/m/Y H:i'),
                ])->values(),
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
