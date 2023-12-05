<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollaboratorIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'collaborator_id' => $this->collaborator_id,
            'description' => $this->description,
        ];
    }
}
