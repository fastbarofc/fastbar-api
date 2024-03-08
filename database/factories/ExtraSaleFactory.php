<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Unit;
use App\Models\Event;

class ExtraSaleFactory extends Factory
{
    public function definition(): array
    {
        $unit = Unit::all()->random();
        $event = Event::all()->random();

        return [
            'description' => $this->faker->sentence(3, true),
            'unit_id'     => $unit->id,
            'quantity'    => $this->faker->numberBetween(10, 40),
            'unit_value'  => $this->faker->numberBetween(10, 300),
            'event_id'    => $event->id,
        ];
    }
}
