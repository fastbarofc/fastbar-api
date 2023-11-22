<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Collaborator;
use App\Models\FisicalPerson;
use App\Models\Person;

class UniqueCpf implements ValidationRule
{
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = str_replace(['.', '-'], '', $value);

        //$person = FisicalPerson::where('cpf', $cpf)->first();
//
        //if ($person) {
        //    $fail('CPF já cadastrado.');
        //}
    }
}
