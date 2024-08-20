<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakItem>
 */
class BreakItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = Unit::all()->random();

        return [
            'description' => $this->faker->word,
            'slug'        => $this->faker->unique()->slug,
            'unit_id'     => $unit->id,
            'value'       => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}
