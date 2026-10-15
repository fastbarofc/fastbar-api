<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\EventStatus;
use Illuminate\Validation\Validator;

trait ValidatesEventStatusReason
{
    private function validateStatusReason(Validator $validator, ?Event $event = null): void
    {
        $status = EventStatus::find($this->input('event_status_id'));

        if (!$status || !in_array($status->name, EventStatus::REQUIRES_REASON, true) || $event?->event_status_id == $status->id) {
            return;
        }

        if (blank($this->input('status_reason'))) {
            $validator->errors()->add('status_reason', "Informe o motivo para mudar o evento para {$status->name}.");
        }
    }
}
