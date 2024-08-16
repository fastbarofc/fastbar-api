<?php

namespace Database\Seeders;

use App\Models\HowDidYouHear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HowDidYouHearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HowDidYouHear::factory(15)->create();
    }
}
