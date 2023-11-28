<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventPlace;

class EventPlaceSeeder extends Seeder
{
    public function run(): void
    {
        EventPlace::factory(10)->hasAddress()->create();
    }
}
