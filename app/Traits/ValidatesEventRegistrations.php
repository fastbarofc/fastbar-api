<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\Partner;
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

        $registrations = [
            'client_id'      => [Client::class, 'O cliente', 'Clientes'],
            'partner_id'     => [Partner::class, 'O parceiro', 'Parceiros'],
            'event_place_id' => [EventPlace::class, 'O local', 'Locais'],
        ];

        $pending = [];

        foreach ($registrations as $field => [$model, $label, $page]) {
            $id = $this->has($field) ? $this->input($field) : $event?->{$field};

            if (!$id || (!$statusChanged && $id == $event->{$field})) {
                continue;
            }

            $registration = $model::find($id);
            $missing = $registration?->missingRequiredFields() ?? [];

            if (!empty($missing)) {
                $pending[] = sprintf(
                    '%s "%s" está com cadastro incompleto (%s). Preencha na página de %s.',
                    $label,
                    $registration->name,
                    implode(', ', $missing),
                    $page
                );
            }
        }

        if (!empty($pending)) {
            $validator->errors()->add(
                'event_status_id',
                "O status {$status->name} exige cadastros completos. " . implode(' ', $pending)
            );
        }
    }
}
