<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;

class EventKitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'=> $this->faker->words(2, true),
            'event_id'=> Event::all()->random()->id,
            'themed_table'=> $this->faker->boolean,
            'optional'=> $this->faker->boolean,
            'value'=> $this->faker->randomFloat(2, 20, 1500),
        ];
    }
}
