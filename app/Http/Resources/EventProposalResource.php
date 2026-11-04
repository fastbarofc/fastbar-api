<?php

namespace App\Http\Resources;

use App\Services\EventProposalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'generated_by' => $this->user?->collaborator?->name ?? $this->user?->name,
            'data' => $this->data,
            'blocks' => array_map(
                fn (array $block) => [
                    'title' => $block['title'],
                    'items' => $block['items'],
                    'line' => $block['line'] ?? null,
                ],
                EventProposalService::blocks($this->data ?? []),
            ),
        ];
    }
}
