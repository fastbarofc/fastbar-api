<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::firstOrCreate(
            ['internal_name' => 'Fastbar'],
            [
                'legal_name' => 'Fastbar',
                'trade_name' => 'Fastbar',
                'type' => 'head_office',
            ]
        );
    }
}
