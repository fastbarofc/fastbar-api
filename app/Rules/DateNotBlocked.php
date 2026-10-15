<?php

namespace App\Rules;

use App\Models\BlockedDate;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateNotBlocked implements ValidationRule
{
    // Só valida quando a data muda: evento que já estava no dia antes do bloqueio continua editável.
    public function __construct(private ?string $currentDate = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $date = Carbon::parse($value);

        if ($date->toDateString() === $this->currentDate) {
            return;
        }

        $blockedDate = BlockedDate::whereDate('date', $date)->first();

        if ($blockedDate) {
            $reason = $blockedDate->description ? " ({$blockedDate->description})" : '';
            $fail("O dia {$date->format('d/m/Y')} está bloqueado{$reason}. Escolha outra data para o evento.");
        }
    }
}
