<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventGift;

class EventGiftSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();
        foreach ($events as $event) {
            EventGift::factory(3)->state(['event_id' => $event->id])->create();
        }
    }
}
