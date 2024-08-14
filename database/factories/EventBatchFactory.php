<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;
use App\Models\EventBatch;

class EventBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_value' => $this->faker->randomNumber(3, true),
            'discount' => $this->faker->randomNumber(2, true),
            'unit_discount' => $this->faker->randomNumber(2, true),
            'guests' => $this->faker->numberBetween(10, 300),
            'min_guests' => $this->faker->numberBetween(10, 50),
            'total_value' => $this->faker->randomNumber(3, true),
            'buffet_value' => $this->faker->randomNumber(3, true),
        ];
    }
}
