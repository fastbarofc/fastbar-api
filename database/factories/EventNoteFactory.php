<?php

namespace Database\Factories;

use App\Models\Collaborator;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventNote>
 */
class EventNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => $this->faker->text(),
            'collaborator_id' => Collaborator::all()->random()->id,
            'checklist' => $this->faker->boolean(),
            'event_id' => Event::all()->random()->id,
        ];
    }
}
