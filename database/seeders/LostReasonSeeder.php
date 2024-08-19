<?php

namespace Database\Seeders;

use App\Models\LostReason;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LostReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LostReason::factory(5)->create();
    }
}
