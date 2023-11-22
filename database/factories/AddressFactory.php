<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\City;
use Faker\Provider\pt_BR\Address;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
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
            'street'    => $this->faker->sentence(),
            'zip_code'  => Address::postcode(),
            'number'    => $this->faker->randomNumber(5, false),
            'district'  => $this->faker->city(),
            'city_id'   => $city->id,
            'state_id'  => $city->state_id,
        ];
    }
}
