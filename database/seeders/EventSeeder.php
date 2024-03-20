<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Material;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::factory(500)->create();

        $legalRepresentatives = Client::where('type', Client::TYPE_FISICAL)->get();
        $materials = Material::all();
    
        foreach ($events as $event) {
            $legalRepresentativesToEvent = $legalRepresentatives->random(3)->pluck('id');
            $materialsToEvent = $materials->random(3)->pluck('id');
            $event->clients()->attach($legalRepresentativesToEvent);
            $event->materials()->attach($materialsToEvent, ['quantity' => rand(1, 10), 'selected' => rand(0, 1)]);
        }
    }
}
