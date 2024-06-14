<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventBatch;

class EventBatchSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        $events->each(function (Event $event) {
            EventBatch::factory()->count(2)->create([
                'event_id' => $event->id,
            ]);
        });
    }
}
