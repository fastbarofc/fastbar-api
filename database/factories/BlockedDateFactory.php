<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BlockedDateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date'=> $this->faker->unique()->dateTimeBetween('now', '+1 years'),
            'description'=> $this->faker->streetName
        ];
    }
}
