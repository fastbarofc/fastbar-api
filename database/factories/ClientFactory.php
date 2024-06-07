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
            'email'=> $this->faker->safeEmail(),
            'phone_1'=> $this->faker->numerify('99##-####'),
            'cnpj' => $type == 'pj' ? $this->faker->unique()->cnpj() : null,
            'name' => $this->faker->name(),
            'cpf' => $type == 'pf' ? $this->faker->unique()->cpf() : null,
            'nickname' => $this->faker->name(),
        ];
    }
}
