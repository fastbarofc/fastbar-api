<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o CNPJ no algoritmo alfanumérico da Receita Federal: os 12 primeiros caracteres
 * são alfanuméricos (A-Z + dígitos) e os 2 últimos são dígitos verificadores numéricos.
 * O valor de cada caractere é o seu código ASCII menos 48 ('0'..'9' => 0..9, 'A'..'Z' => 17..42).
 */
class Cnpj implements ValidationRule
{
    private const ASCII_BASE = 48;
    private const FIRST_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    private const SECOND_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = (string) unmaskCnpj($value);

        if (!preg_match('/^[A-Z0-9]{12}\d{2}$/', $cnpj) || preg_match('/^(.)\1+$/', $cnpj)) {
            $fail('O campo CNPJ deve ser um CNPJ válido.');

            return;
        }

        $base = substr($cnpj, 0, 12);
        $firstDigit = $this->digit($base, self::FIRST_WEIGHTS);
        $secondDigit = $this->digit($base . $firstDigit, self::SECOND_WEIGHTS);

        if ($cnpj !== $base . $firstDigit . $secondDigit) {
            $fail('O campo CNPJ deve ser um CNPJ válido.');
        }
    }

    private function digit(string $base, array $weights): int
    {
        $sum = 0;

        foreach (str_split($base) as $index => $char) {
            $sum += (ord($char) - self::ASCII_BASE) * $weights[$index];
        }

        $rest = $sum % 11;

        return $rest < 2 ? 0 : 11 - $rest;
    }
}
