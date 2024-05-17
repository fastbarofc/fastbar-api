<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Unit;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $unit = Unit::all()->random();

        return [
            'name'              => $this->faker->unique()->word,
            'unit_id'           => $unit->id,
            'value'             => $this->faker->randomFloat(2, 20, 3000),
            'marketable'        => $this->faker->boolean,
            'display_checklist' => $this->faker->boolean,
            'own_production'    => $this->faker->boolean,
        ];
    }
}
