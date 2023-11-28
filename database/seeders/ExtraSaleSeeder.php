<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExtraSale;

class ExtraSaleSeeder extends Seeder
{
    public function run(): void
    {
        ExtraSale::factory(10)->create();
    }
}
