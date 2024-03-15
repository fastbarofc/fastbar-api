<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::factory(500)->create();

        $legalRepresentatives = Client::where('type', Client::TYPE_FISICAL)->get();
    
        foreach ($events as $event) {
            $legalRepresentativesToEvent = $legalRepresentatives->random(3)->pluck('id');
            $event->clients()->attach($legalRepresentativesToEvent);
        }
    }
}
