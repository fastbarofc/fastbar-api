<?php

namespace Database\Seeders;

use App\Models\Failure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FailureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Failure::factory(5)->create();
    }
}
