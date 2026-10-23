<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\EventStatus;
use Illuminate\Validation\Validator;

trait ValidatesEventRegistrations
{
    private function validateRegistrationsForStatus(Validator $validator, ?Event $event = null): void
    {
        $status = EventStatus::find($this->input('event_status_id', $event?->event_status_id));

        if (!$status || !in_array($status->name, EventStatus::REQUIRES_COMPLETE_REGISTRATIONS, true)) {
            return;
        }

        $statusChanged = $event?->event_status_id != $status->id;

        $ids = [];

        foreach (array_keys(Event::REQUIRED_REGISTRATIONS) as $field) {
            $id = $this->has($field) ? $this->input($field) : $event?->{$field};

            if ($id && ($statusChanged || $id != $event->{$field})) {
                $ids[$field] = $id;
            }
        }

        $pending = Event::incompleteRegistrationMessages($ids);

        if (!empty($pending)) {
            $validator->errors()->add(
                'event_status_id',
                "O status {$status->name} exige cadastros completos. " . implode(' ', $pending)
            );
        }
    }
}
