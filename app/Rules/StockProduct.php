<?php

namespace App\Rules;

use App\Services\Stock\StockOverview;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StockProduct implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! StockOverview::stockProducts()->whereKey($value)->exists()) {
            $fail('Produto inválido para o estoque. Drinks não entram no estoque, só os itens da composição.');
        }
    }
}
