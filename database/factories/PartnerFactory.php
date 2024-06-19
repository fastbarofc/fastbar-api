<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Partner;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->company,
            'responsible' => $this->faker->name,
            'email'       => $this->faker->safeEmail,
            'phone_1'     => $this->faker->numerify('##99#######'),
        ];
    }
}
