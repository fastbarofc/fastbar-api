<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'client_id'          => $this->client_id,
            'event_id'           => $this->event_id,
            'client'             => $this->whenLoaded('client'),
            'invoice_client_id'  => $this->invoice_client_id,
            'invoice_client'     => $this->whenLoaded('invoiceClient'),
            'collaborator_id'    => $this->collaborator_id,
            'collaborator'       => $this->whenLoaded('collaborator'),
            'event_type_id'      => $this->event_type_id,
            'type'               => $this->whenLoaded('eventType'),
            'event_place_id'     => $this->event_place_id,
            'event_place'        => $this->whenLoaded('eventPlace'),
            'event_status_id'    => $this->event_status_id,
            'status'             => $this->whenLoaded('eventStatus'),
            'lease'              => $this->lease,
            'duration'           => $this->duration,
            'date'               => $this->date ? Carbon::parse($this->date)->format('Y-m-d') : null,
            'time'               => $this->time ? Carbon::createFromFormat(strlen($this->time) == 5 ? 'H:i' : 'H:i:s', $this->time)->format('H:i') : null,
            'duration_after'     => $this->duration_after,
            'contract_body'      => $this->contract_body,
            'parthenon_courtesies' => $this->parthenon_courtesies,
            'partner_courtesies' => $this->partner_courtesies,
            'courtesies'         => $this->courtesies,
            'unit_insurance'     => $this->unit_insurance,
            'buffet_value'       => $this->buffet_value,
            'sub_total'          => $this->sub_total,
            'event_guests'       => $this->event_guests,
            'present_guests'     => $this->present_guests,
            'guest_unit_value'   => $this->guest_unit_value,
            'excess_unit_value'  => $this->excess_unit_value,
            'discount'           => $this->discount,
            'unit_discount'      => $this->unit_discount,
            'total_value'        => $this->total_value,
            'partner_id'         => $this->partner_id,
            'decorator_id'       => $this->decorator_id,
            'is_draft'           => $this->is_draft,
            'event_capture_id'   => $this->event_capture_id,
            'ceremonial_id'      => $this->ceremonial_id,
            'legal_representatives' => $this->whenLoaded('legalRepresentatives', fn() => $this->legalRepresentatives->pluck('id')),
            'materials'          => $this->whenLoaded('materials', fn() => $this->materials->map(fn($material) => [
                'id'          => $material->id,
                'description' => $material->description,
                'quantity'    => $material->pivot->quantity ?? 0,
                'selected'    => $material->pivot->selected ?? null,
            ])),
            'extra_sales'        => $this->whenLoaded('extraSales'),
            'event_batches'      => $this->whenLoaded('eventBatches'),
            'event_notes'        => $this->whenLoaded('eventNotes', fn() => EventNoteResource::collection($this->eventNotes)),
        ];
    }
}
