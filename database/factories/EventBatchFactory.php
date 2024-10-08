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
            'guests' => $this->faker->numberBetween(10, 300),
        ];
    }
}
