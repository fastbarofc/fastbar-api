<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $money = fn ($v) => $v !== null ? (float) $v : null;

        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'phone'                => $this->phone,
            'email'                => $this->email,
            'instagram'            => $this->instagram,
            'city'                 => $this->city,
            'served_city_id'       => $this->served_city_id,
            'source'               => $this->source,
            'temperature'          => $this->temperature,
            'tags'                 => $this->tags ?? [],
            'partner_id'           => $this->partner_id,
            'partner_name'         => $this->partner?->name,
            'event_type_id'        => $this->event_type_id,
            'event_type_name'      => $this->eventType?->name,
            'event_date'           => $this->event_date?->format('Y-m-d'),
            'guests'               => $this->guests,
            'estimated_value'      => $money($this->estimated_value),
            'expected_viability'   => $money($this->expected_viability),
            'real_viability'       => $money($this->real_viability),
            'meeting_date'         => $this->meeting_date?->format('Y-m-d'),
            'meeting_result'       => $this->meeting_result,
            'meeting_notes'        => $this->meeting_notes,
            'proposal_sent_at'     => $this->proposal_sent_at?->format('Y-m-d'),
            'proposal_valid_until' => $this->proposal_valid_until?->format('Y-m-d'),
            'final_value'          => $money($this->final_value),
            'discount_percent'     => $money($this->discount_percent),
            'approval_status'      => $this->approval_status,
            'approval_request_note' => $this->approval_request_note,
            'approval_note'        => $this->approval_note,
            'approved_by'          => $this->approved_by,
            'approved_by_name'     => $this->approvedBy?->name,
            'approved_at'          => $this->approved_at,
            'first_contact_at'     => $this->first_contact_at,
            'proposal_valid_until_effective' => $this->proposalValidUntil()?->toDateString(),
            'pre_reserve'          => $this->holdsPreReserve(),
            'missing_fields'       => $this->missingForNextStage(),
            'market_warnings'      => app(\App\Services\LeadRulesService::class)->marketWarnings($this->resource),
            'pre_reserve_released_at' => $this->pre_reserve_released_at?->toDateTimeString(),
            'pre_reserve_released_by_name' => $this->pre_reserve_released_by ? $this->preReserveReleasedBy?->name : null,
            'deposit_value'        => $money($this->deposit_value),
            'deposit_date'         => $this->deposit_date?->format('Y-m-d'),
            'tasting'              => $this->tasting,
            'tasting_date'         => $this->tasting_date?->format('Y-m-d'),
            'tasting_result'       => $this->tasting_result,
            'tasting_participants' => $this->tasting_participants,
            'stage'                => $this->stage,
            'position'             => $this->position,
            'user_id'              => $this->user_id,
            'user_name'            => $this->user?->name,
            'imported_owner'       => $this->imported_owner,
            'lost_reason_id'       => $this->lost_reason_id,
            'lost_reason_name'     => $this->lostReason?->name,
            'lost_note'            => $this->lost_note,
            'competitor'           => $this->competitor,
            'competing_company_id' => $this->competing_company_id,
            'competing_company_name' => $this->competingCompany?->name,
            'client_id'            => $this->client_id,
            'client_name'          => $this->client?->name,
            'event_id'             => $this->event_id,
            'event_name'           => $this->event?->name,
            'next_contact_date'    => $this->next_contact_date?->format('Y-m-d'),
            'next_action'          => $this->next_action,
            'observation'          => $this->observation,
            'stage_changed_at'     => $this->stage_changed_at,
            'days_in_stage'        => $this->daysInStage(),
            'stalled'              => $this->isStalled(),
            'comments_count'       => $this->comments_count ?? null,
            'activities_count'     => $this->activities_count ?? null,
            'comments'             => $this->whenLoaded('comments', fn () => LeadCommentResource::collection($this->comments)),
            'activities'           => $this->whenLoaded('activities', fn () => LeadActivityResource::collection($this->activities)),
            'no_answer_attempts'   => $this->when($this->relationLoaded('activities'), fn () => $this->noAnswerAttempts()),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }

    /** Orientação (não trava): dados sugeridos para o lead avançar para a próxima etapa do caminho principal. */
    private function missingForNextStage(): array
    {
        $path = \App\Models\Lead::MAIN_PATH;
        $i = array_search($this->stage, $path, true);
        if ($i === false || !isset($path[$i + 1])) {
            return [];
        }

        return array_values(\App\Models\Lead::missingFor($this->stage, $path[$i + 1], $this->getAttributes()));
    }
}
