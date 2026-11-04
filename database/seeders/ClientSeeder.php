<?php

namespace Database\Seeders;

use App\Enums\AddressTypeEnum;
use App\Models\Address;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ClientRepresentative;
use App\Models\Event;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory(20)->create()->each(function ($client) {
            $client->address()->save(Address::factory()->make());

            if ($client->type === Client::TYPE_LEGAL) {
                ClientRepresentative::factory()->for($client)->create()
                    ->address()->save(Address::factory()->make(['type' => AddressTypeEnum::REPRESENTATIVE]));
            }
        });
    }
}
