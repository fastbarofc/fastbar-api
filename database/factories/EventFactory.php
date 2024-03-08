<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Collaborator;
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
            'client_id' => Client::all()->random()->id,
            'collaborator_id' => Collaborator::all()->random()->id,
            'event_type_id'=> $eventType->id,
            'event_place_id'=> $eventPlace->id,
            'event_status_id'=> $eventStatus->id,
            'lease'=> $this->faker->numberBetween(1000, 10000),
            'guests'=> $this->faker->numberBetween(10, 300),
            'min_guests'=> $this->faker->numberBetween(10, 50),
            'duration'=> $this->faker->numberBetween(1, 12), 
            'durantion_after'=> $this->faker->numberBetween(1, 12),
            'date_time'=> $this->faker->dateTimeBetween('-1 years', '+1 years'),
            'unit_value'=> $this->faker->randomNumber(3, true),
            'discount'=> $this->faker->randomNumber(2, true),
            'unit_discount'=> $this->faker->randomNumber(2, true)
        ];
    }
}
