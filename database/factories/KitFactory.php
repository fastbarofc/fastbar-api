<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'=> $this->faker->name,
            'themed_table'=> $this->faker->boolean,
            'optional'=> $this->faker->boolean,
            'value'=> $this->faker->randomFloat(2, 20, 1500),
        ];
    }
}
