<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventNote;

class EventNoteSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        $events->each(function (Event $event) {
            EventNote::factory()->count(3)->create([
                'event_id' => $event->id,
            ]);
        });
    }
}
