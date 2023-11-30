<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\LegalClient;

class LegalClientSeeder extends Seeder
{
    public function run(): void
    {
        $legalClients = Client::where('type', Client::TYPE_LEGAL)->get();

        foreach ($legalClients as $client) {
            LegalClient::factory(1)->state(['client_id' => $client->id])->create();
        }
    }
}
