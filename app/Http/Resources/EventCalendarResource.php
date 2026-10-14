<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batchGuestsCount = $this->eventBatches->sum('guests');

        $place = "";

        if (!empty($this->eventPlace)) {
            $place = $this->eventPlace->name;

            if (!empty($this->eventPlace->address)) {
                $place .= ' (' . $this->eventPlace->address->city->name . ' - ' . $this->eventPlace->address->city->state->letter . ')';
            }
        }

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'date'               => Carbon::parse($this->date)->format('Y-m-d'),
            'time'               => $this->time ? Carbon::parse($this->time)->format('H:i') : null,
            'event_place'        => $place,
            'guests'             => $this->event_guests + $batchGuestsCount,
            'event_status'       => $this->eventStatus->name,
            'updated_at'         => $this->updated_at,
            'companies'          => $this->whenLoaded('companies', fn() => $this->companies->map(fn($c) => [
                'id' => $c->id,
                'internal_name' => $c->internal_name
            ])->values()->toArray()),

            // Campos abaixo alimentam o modal de detalhes do calendário.
            // Vêm todos de relações já carregadas no indexCalendar (sem query extra).
            'client_name'        => $this->client?->name,
            'client_phone'       => $this->client?->phone_1,
            'event_type'         => $this->eventType?->name,
            'collaborator'       => $this->collaborator?->name,
            'ceremonial'         => $this->ceremonial?->name,
            'duration'           => $this->duration,
            'duration_after'     => $this->duration_after,
            'bar_counter_type'   => $this->bar_counter_type,
            'cup_type'           => $this->cup_type,
            'min_guests'         => $this->min_guests,
            'present_guests'     => $this->present_guests,
            'total_value'        => $this->total_value,

            // Abas "Kits" e "Anotações" do modal. Vêm das relações carregadas
            // no indexCalendar, sem requisição adicional ao abrir o modal.
            'kits'               => $this->whenLoaded('eventKits', fn() => $this->buildKitTree()),
            'notes'              => $this->whenLoaded('eventNotes', fn() => $this->eventNotes
                ->sortByDesc('created_at')
                ->map(fn($note) => [
                    'id'         => $note->id,
                    'text'       => $note->text,
                    'checklist'  => (bool) $note->checklist,
                    'user'       => $note->user?->name,
                    'created_at' => $note->created_at,
                ])->values()->toArray()),
        ];
    }

    /**
     * Monta a árvore de kits (kit raiz -> subkits -> produtos) a partir da
     * coleção já carregada, sem disparar novas consultas.
     */
    private function buildKitTree()
    {
        $kits = $this->eventKits;

        $build = function ($parentId) use (&$build, $kits) {
            return $kits
                ->where('event_kit_id', $parentId)
                ->sortBy(fn($kit) => [$kit->ordination ?? PHP_INT_MAX, $kit->name])
                ->map(fn($kit) => [
                    'id'          => $kit->id,
                    'name'        => $kit->name,
                    'value'       => $kit->value,
                    'optional'    => (bool) $kit->optional,
                    'observation' => $kit->observation,
                    'products'    => $kit->relationLoaded('products')
                        ? $kit->products->map(fn($product) => [
                            'id'       => $product->id,
                            'name'     => $product->name,
                            'quantity' => $product->pivot->quantity,
                            'unit'     => $product->pivot->unit,
                            'optional' => (bool) $product->pivot->optional,
                        ])->values()->toArray()
                        : [],
                    'children'    => $build($kit->id),
                ])
                ->values()
                ->toArray();
        };

        return $build(null);
    }
}
