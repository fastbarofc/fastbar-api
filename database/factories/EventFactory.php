<?php

namespace Database\Factories;

use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Decorator;
use App\Models\EventCapture;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EventType;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\Partner;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $eventType = EventType::all()->random();
        $eventPlace = EventPlace::all()->random();
        $eventStatus = EventStatus::all()->random();

        return [
            'name' => $this->faker->company,
            'client_id' => Client::all()->random()->id,
            'invoice_client_id' => Client::all()->random()->id,
            'collaborator_id' => Collaborator::all()->random()->id,
            'event_type_id' => $eventType->id,
            'event_place_id' => $eventPlace->id,
            'event_status_id' => $eventStatus->id,
            'lease' => $this->faker->numberBetween(1000, 10000),
            'duration' => $this->faker->numberBetween(1, 12),
            'duration_after' => $this->faker->numberBetween(1, 12),
            'date_time' => $this->faker->dateTimeBetween('-1 years', '+1 years'),
            'total_value' => $this->faker->randomNumber(3, true),
            'partner_id' => Partner::all()->random()->id,
            'decorator_id' => Decorator::all()->random()->id,
            'event_capture_id' => EventCapture::all()->random()->id,
            'ceremonial_id' => Ceremonial::all()->random()->id,
            'contract_body' => $this->faker->text,
            'parthenon_courtesies' => $this->faker->randomNumber(2, true),
            'partner_courtesies' => $this->faker->randomNumber(2, true),
            'unit_insurance' => $this->faker->randomNumber(2, true),
            'buffet_value' => $this->faker->randomNumber(3, true),
        ];
    }
}
