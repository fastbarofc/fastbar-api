<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FisicalClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'=> $this->faker->unique()->name,
            'cpf'=> $this->faker->unique()->cpf(),
            'client_id'=> '',
        ];
    }
}
