<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Models\Material;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'old_system_id'      => $this->old_system_id,
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
            'closed_at' => $this->closed_at ? $this->closed_at->format('Y-m-d H:i:s') : null,
            'duration_after'     => $this->duration_after,
            'glass_cup_hours'    => $this->glass_cup_hours,
            'contract_body'      => $this->contract_body,
            'post_event_consumption_report_file_id' => $this->post_event_consumption_report_file_id,
            'post_event_consumption_report_file_name' => $this->postEventConsumptionReportFile?->name,
            'post_event_consumption_report_file_url' => $this->postEventConsumptionReportFile ? asset('storage/' . $this->postEventConsumptionReportFile->path) : null,
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
            'min_guests'         => $this->min_guests,
            'discount'           => $this->discount,
            'unit_discount'      => $this->unit_discount,
            'total_value'        => $this->total_value,
            'partner_id'         => $this->partner_id,
            'decorator_id'       => $this->decorator_id,
            'is_draft'           => $this->is_draft,
            'event_capture_id'   => $this->event_capture_id,
            'ceremonial_id'      => $this->ceremonial_id,
            'legal_representatives' => $this->whenLoaded('legalRepresentatives', fn() => $this->legalRepresentatives->pluck('id')),
            'materials' => Material::all()->map(function ($material) {
                $materialEvent = $this->materials->where('id', $material->id)->first();
                return [
                    'id' => $material->id,
                    'description' => $material->description,
                    'quantity' => $materialEvent ? $materialEvent->pivot->quantity : 0,
                    'selected' => $materialEvent ? ($materialEvent->pivot->selected === null ? '-1' : $materialEvent->pivot->selected) : null,
                ];
            }),
            'extra_sales'        => $this->whenLoaded('extraSales'),
            'event_batches'      => $this->whenLoaded('eventBatches'),
            'event_notes'        => $this->whenLoaded('eventNotes', fn() => EventNoteResource::collection($this->eventNotes)),
            'payment_plan' => new PaymentPlanResource($this->whenLoaded('paymentPlan')),

            'receivables' => $this->whenLoaded('receivables', function () {
                return $this->receivables->map(function ($receivable) {
                    return [
                        'description' => $receivable->description,
                        'amount' => $receivable->amount,
                        'due_date' => $receivable->due_date,
                        'status' => $receivable->status,
                        'payment_method' => $receivable->paymentMethod,
                    ];
                });
            }),
            'companies' => $this->whenLoaded('companies', fn () => $this->companies->map(fn ($c) => ['id' => $c->id, 'internal_name' => $c->internal_name])),
        ];
    }
}
