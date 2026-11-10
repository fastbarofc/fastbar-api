<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('user:id,name');

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name ?? 'Usuário removido',
            'text' => $this->text,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
