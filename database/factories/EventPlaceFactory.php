<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EventPlaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->streetName,
            'responsible' => $this->faker->name,
            'description' => $this->faker->country,
            'phone_1'     => $this->faker->numerify('##99#######'),
            'email'       => $this->faker->safeEmail,
        ];
    }
}
