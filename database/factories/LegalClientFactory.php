<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LegalClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name'=> $this->faker->unique()->company,
            'cnpj'=> $this->faker->unique()->cnpj(),
            'client_id'=> '',
        ];
    }
}
