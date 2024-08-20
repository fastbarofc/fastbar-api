<?php

namespace Database\Seeders;

use App\Models\OfficeBudget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OfficeBudget::factory(15)->create(); 
    }
}
