<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;

class EventGiftFactory extends Factory
{
    public function definition(): array
    {
        $event = Event::all()->random();
        return [
            'description'=> $this->faker->word,
            'event_id'=> $event->id,
            'quantity'=> $this->faker->numberBetween($min = 10, $max = 30),
        ];
    }
}
