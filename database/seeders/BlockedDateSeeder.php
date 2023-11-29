<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BlockedDate;

class BlockedDateSeeder extends Seeder
{
    public function run(): void
    {
        BlockedDate::factory(5)->create();
    }
}
