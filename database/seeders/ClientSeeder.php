<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Event;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory(20)->create()->each(function ($client) {
            $client->address()->save(Address::factory()->make());
        });
    }
}
