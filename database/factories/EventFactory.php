<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EventType;
use App\Models\EventPlace;
use App\Models\EventStatus;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $eventType = EventType::all()->random();
        $eventPlace = EventPlace::all()->random();
        $eventStatus = EventStatus::all()->random();

        return [
            'name'=> $this->faker->company,
            'event_type_id'=> $eventType->id,
            'event_place_id'=> $eventPlace->id,
            'event_status_id'=> $eventStatus->id,
            'lease'=> $this->faker->numberBetween(1000, 10000),
            'guests'=> $this->faker->numberBetween(10, 300),
            'min_guests'=> $this->faker->numberBetween(10, 50),
            'date_time'=> $this->faker->dateTime,
            'value_unit'=> $this->faker->randomNumber(3, true),
            'descont_unit'=> $this->faker->randomNumber(2, true),
        ];
    }
}
