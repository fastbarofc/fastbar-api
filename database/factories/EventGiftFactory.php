<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;

class EventGiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description'=> $this->faker->words($nb = 3, $asText = true),
            'quantity'=> $this->faker->numberBetween($min = 10, $max = 30),
        ];
    }
}
