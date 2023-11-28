<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventGift;

class EventGiftSeeder extends Seeder
{
    public function run(): void
    {
        EventGift::factory(10)->create();
    }
}
