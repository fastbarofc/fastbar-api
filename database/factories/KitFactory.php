<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'=> $this->faker->words(2, true),
            'themed_table'=> $this->faker->boolean,
            'optional'=> $this->faker->boolean,
            'value'=> $this->faker->randomFloat(2, 20, 1500),
        ];
    }
}
