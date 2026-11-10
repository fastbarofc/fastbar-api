<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'active'     => $this->active,
            'is_employee' => (bool) $this->is_employee,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'roles'      => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'collaborator_id' => $this->whenLoaded('collaborator', fn () => $this->collaborator?->id),
        ];
    }
}