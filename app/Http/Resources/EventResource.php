<?php

namespace App\Http\Resources;

use App\Models\EventKit;
use App\Models\Material;
use App\Models\OfficeEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $eventKits = EventKit::whereNull('event_kit_id')->where('event_id', $this->id)->get();
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'client_id'          => $this->client_id,
            'event_id'           => $this->event_id,
            'client'        => $this->client,
            'invoice_client_id'  => $this->invoice_client_id,
            'invoice_client'  => $this->invoiceClient,
            'collaborator_id'    => $this->collaborator_id,
            'collaborator'    => $this->collaborator,
            'event_type_id'      => $this->event_type_id,
            'type'               => $this->eventType,
            'event_place_id'     => $this->event_place_id,
            'event_place'        => $this->eventPlace,
            'event_status_id'    => $this->event_status_id,
            'status'             => $this->eventStatus,
            'lease'              => $this->lease,
            'duration'           => $this->duration,
            'date_time'          => $this->date_time?->format('Y-m-d\TH:i'),
            'duration_after'    => $this->duration_after,
            'unit_value'         => $this->unit_value,
            'discount'       => $this->discount,
            'unit_discount'       => $this->unit_discount,
            'contract_body'      => $this->contract_body,
            'parthenon_courtesies' => $this->parthenon_courtesies,
            'partner_courtesies'   => $this->partner_courtesies,
            'courtesies'         => $this->courtesies,
            'unit_insurance'     => $this->unit_insurance,
            'buffet_value'       => $this->buffet_value,
            'total_value'        => $this->total_value,
            'partner_id'         => $this->partner_id,
            'decorator_id' => $this->decorator_id,
            'is_draft' => $this->is_draft,
            'event_capture_id' => $this->event_capture_id,
            'ceremonial_id' => $this->ceremonial_id,
            'legal_representatives' => $this->legal_representatives->pluck('id')->toArray(),
            'event_gifts'        => $this->eventGifts,
            'competing_company_id' => $this->competing_company_id,
            'lost_reason_id' => $this->lost_reason_id,
            'materials' => Material::all()->map(function ($material) {
                $materialEvent = $this->materials->where('id', $material->id)->first();
                return [
                    'id' => $material->id,
                    'description' => $material->description,
                    'quantity' => $materialEvent ? $materialEvent->pivot->quantity : 0,
                    'selected' => $materialEvent ? ($materialEvent->pivot->selected === null ? '-1' : $materialEvent->pivot->selected) : null,
                ];
            }),
            'office_events' => OfficeEvent::all()->map(function ($officeEvent) {
                $eventOfficeEvent = $this->officeEvents->where('id', $officeEvent->id)->first();
                return [
                    'id' => $officeEvent->id,
                    'description' => $officeEvent->description,
                    'quantity' => $eventOfficeEvent ? $eventOfficeEvent->pivot->quantity : 0,
                ];
            }),
            'extra_sales' => $this->extraSales,
            'event_batches' => $this->eventBatches,
            'event_notes' => EventNoteResource::collection($this->eventNotes),
        ];
    }
}
