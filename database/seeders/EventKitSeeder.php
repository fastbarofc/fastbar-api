<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventKit;

class EventKitSeeder extends Seeder
{

    public function run(): void
    {
        $grandparentKits = EventKit::factory(15)->create();

        $grandparentKits = $grandparentKits->take(12);

        foreach ($grandparentKits as $parent) {
            $eventKitNames = ['Bar Principal', 'Bar Secundario', 'Camarim'];

            foreach ($eventKitNames as $eventKitName) {
                EventKit::factory()
                ->state(['event_kit_id'=> $parent->id, 'event_id' => $parent->event_id, 'name' => $eventKitName])
                ->create();
            }
        }

        $parentKits = EventKit::whereNotNull('event_kit_id')->take(25);

        foreach ($parentKits as $parent) {
            EventKit::factory(2)
            ->state(['event_kit_id'=> $parent->id, 'event_id' => $parent->event_id])
            ->create();
        }
    }
}
