<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Supplier;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{

    public function definition(): array
    {
        return [
            'corporate_name' => $this->faker->company(),
            'fantasy_name' => $this->faker->companySuffix(),
            'cnpj' => $this->faker->cnpj(),
            'state_registration' => $this->faker->numerify('###########'),
            'phone_1' => $this->faker->numerify('##99#######'),
            'phone_2' => $this->faker->numerify('##99#######'),
        ];
    }
}
