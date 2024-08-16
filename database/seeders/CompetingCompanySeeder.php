<?php

namespace Database\Seeders;

use App\Models\CompetingCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompetingCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompetingCompany::factory(15)->create();
    }
}
