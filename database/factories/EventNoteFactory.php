<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
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
            'checklist' => $this->faker->boolean(),
            'user_id' => User::query()->pluck('id')->random(),
            'event_id' => Event::query()->pluck('id')->random(),

        ];
    }
}
