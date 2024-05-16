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
            EventKit::factory(3)
            ->state(['event_kit_id'=> $parent->id])
            ->create();
        }

        $parentKits = EventKit::whereNotNull('event_kit_id')->take(25);

        foreach ($parentKits as $parent) {
            EventKit::factory(2)
            ->state(['event_kit_id'=> $parent->id])
            ->create();
        }
    }
}
