<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'=> $this->faker->randomElement([Client::TYPE_FISICAL, Client::TYPE_LEGAL]),
            'how_met'=> $this->faker->words(3, true),
            'email'=> $this->faker->safeEmail(),
            'phone_1'=> $this->faker->numerify('99##-####'),
        ];
    }
}
