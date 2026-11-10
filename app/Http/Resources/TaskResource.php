<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['users:id,name', 'sector:id,name', 'event:id,name,date', 'creator:id,name']);
        if (!isset($this->comments_count)) {
            $this->loadCount('comments');
        }

        $today = now()->startOfDay();
        $daysLate = $this->status !== 'done' && $this->due_date && $this->due_date->lt($today)
            ? (int) $this->due_date->diffInDays($today)
            : 0;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'users' => $this->users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->values(),
            'user_ids' => $this->users->pluck('id')->values(),
            'sector_id' => $this->sector_id,
            'sector_name' => $this->sector?->name,
            'event_id' => $this->event_id,
            'event_name' => $this->event?->name,
            'event_date' => $this->event?->date ? Carbon::parse($this->event->date)->format('Y-m-d') : null,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_time' => $this->due_time ? substr($this->due_time, 0, 5) : null,
            'priority' => $this->priority,
            'status' => $this->status,
            'recurrence' => $this->recurrence,
            'is_overdue' => $daysLate > 0,
            'is_due_today' => $this->status !== 'done' && $this->due_date && $this->due_date->isSameDay($today),
            'days_late' => $daysLate,
            'comments_count' => (int) $this->comments_count,
            'completed_at' => $this->completed_at?->format('d/m/Y H:i'),
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
