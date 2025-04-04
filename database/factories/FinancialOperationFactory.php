<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialOperation>
 */
class FinancialOperationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'account' => $this->faker->randomElement(['analytic', 'synthetic']),
            'nature' => $this->faker->randomElement(['revenue', 'expense']),
            'type' => $this->faker->randomElement(['fixed', 'variable']),
        ];
    }
}
