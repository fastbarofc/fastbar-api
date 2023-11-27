<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventPlace>
 */
class EventPlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = City::all()->random();

        return [
            'name'        => $this->faker->streetName,
            'responsible' => $this->faker->name,
            'description' => $this->faker->country,
            'phone_1'     => $this->faker->numerify('99##-####'),
            'email'       => $this->faker->safeEmail,
        ];
    }
}
