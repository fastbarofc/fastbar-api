<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Stock $stock) {
            $stock->address()->save(Address::factory()->make());
        });
    }
}
