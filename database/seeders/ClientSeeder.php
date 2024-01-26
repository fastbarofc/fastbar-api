<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Event;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory(20)->create();

        $event = Event::first();

        $event->clients()->attach(1, ['main' => true]);
    }
}
