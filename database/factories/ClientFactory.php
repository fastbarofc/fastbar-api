<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement([Client::TYPE_FISICAL, Client::TYPE_LEGAL]);

        return [
            'type'=> $type,
            'how_met'=> $this->faker->words(3, true),
            'email'=> $this->faker->safeEmail(),
            'phone_1'=> $this->faker->numerify('99##-####'),

            'company_name' => $type == 'pj' ? $this->faker->unique()->company : null,
            'cnpj' => $type == 'pj' ? $this->faker->unique()->cnpj() : null,

            'name' => $type == 'pf' ? $this->faker->unique()->name : null,
            'cpf' => $type == 'pf' ? $this->faker->unique()->cpf() : null,
        ];
    }
}
