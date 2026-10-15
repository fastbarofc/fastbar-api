<?php

namespace App\Rules;

use App\Models\Event;
use App\Models\EventStatus;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateWithoutEvents implements ValidationRule
{
    public function __construct(private ?string $currentDate = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $date = Carbon::parse($value);

        if ($date->toDateString() === $this->currentDate) {
            return;
        }

        $events = Event::whereDate('date', $date)
            ->whereDoesntHave('eventStatus', fn ($query) => $query->whereIn('name', EventStatus::LOST))
            ->get(['id', 'name']);

        if ($events->isNotEmpty()) {
            $names = $events->map(fn (Event $event) => $event->name ?: "Evento #{$event->id}")->join(', ');
            $fail("Não é possível bloquear o dia {$date->format('d/m/Y')}: já existe evento nele ({$names}).");
        }
    }
}
