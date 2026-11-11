<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiddingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'agency'         => $this->agency,
            'notice_number'  => $this->notice_number,
            'opening_date'   => $this->opening_date,
            'opening_time'   => $this->opening_time ? substr($this->opening_time, 0, 5) : null,
            'status'         => $this->status,
            'observation'    => $this->observation,
            'summary'        => $this->summary,
            'notice_file'    => $this->noticeFile ? [
                'id'   => $this->noticeFile->id,
                'name' => $this->notice_file_name ?: $this->noticeFile->name,
                'url'  => asset('storage' . $this->noticeFile->path),
            ] : null,
            'comments_count' => $this->comments_count ?? null,
            'comments'       => $this->whenLoaded('comments', fn () => BiddingCommentResource::collection($this->comments)),
            'created_at'     => $this->created_at,
        ];
    }
}
