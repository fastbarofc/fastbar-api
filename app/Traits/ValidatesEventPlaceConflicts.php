<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\EventStatus;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesEventPlaceConflicts
{
    private function validatePlaceConflicts(Validator $validator, ?Event $event = null): void
    {
        $status = EventStatus::find($this->input('event_status_id', $event?->event_status_id));
        $placeId = $this->input('event_place_id', $event?->event_place_id);
        $date = $this->input('date', $event?->date);

        if (!$status || !$placeId || !$date || !in_array($status->name, EventStatus::CHECKS_PLACE_CONFLICT, true)) {
            return;
        }

        $date = Carbon::parse($date)->toDateString();
        $samePlaceAndDate = $event && $placeId == $event->event_place_id && $date === $event->date?->toDateString();

        // Quem já estava confirmado neste local e dia não cria conflito novo ao trocar de status,
        // e um conflito antigo não pode travar o resto do fluxo.
        if ($samePlaceAndDate && ($status->id == $event->event_status_id || in_array($event->eventStatus?->name, EventStatus::CONFIRMED, true))) {
            return;
        }

        $conflicts = Event::query()
            ->where('event_place_id', $placeId)
            ->whereDate('date', $date)
            ->when($event, fn ($query) => $query->whereKeyNot($event->id))
            ->whereHas('eventStatus', fn ($query) => $query->whereIn('name', EventStatus::CONFIRMED))
            ->with('eventStatus')
            ->get();

        if ($conflicts->isEmpty()) {
            return;
        }

        $names = $conflicts->map(fn (Event $conflict) => "{$conflict->name} ({$conflict->eventStatus->name})")->implode(', ');

        $validator->errors()->add(
            'event_place_id',
            "Já existe evento confirmado neste local em " . Carbon::parse($date)->format('d/m/Y') . ": {$names}. Escolha outra data ou outro local."
        );
    }
}
