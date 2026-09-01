<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = (string) unmaskCpf($value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            $fail('O campo CPF deve ser um CPF válido.');

            return;
        }

        foreach ([9, 10] as $position) {
            if ($this->digit($cpf, $position) !== (int) $cpf[$position]) {
                $fail('O campo CPF deve ser um CPF válido.');

                return;
            }
        }
    }

    private function digit(string $cpf, int $position): int
    {
        $sum = 0;

        for ($i = 0; $i < $position; $i++) {
            $sum += (int) $cpf[$i] * ($position + 1 - $i);
        }

        $digit = 11 - ($sum % 11);

        return $digit >= 10 ? 0 : $digit;
    }
}
