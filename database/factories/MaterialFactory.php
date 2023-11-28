<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description'=> $this->faker->unique()->word,
            'quantity'=>$this->faker->randomNumber(3, false),
        ];
    }
}
