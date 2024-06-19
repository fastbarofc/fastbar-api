<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DecoratorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'    => $this->faker->name,
            'email'   => $this->faker->safeEmail,
            'phone_1' => $this->faker->numerify('##99#######'),
        ];
    }
}
