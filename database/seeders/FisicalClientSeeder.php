<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\FisicalClient;

class FisicalClientSeeder extends Seeder
{
    public function run(): void
    {
        $fisicalClients = Client::where('type', Client::TYPE_FISICAL)->get();

        foreach ($fisicalClients as $client) {
            FisicalClient::factory(1)->state(['client_id' => $client->id])->create();
        }
    }
}
