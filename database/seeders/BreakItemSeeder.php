<?php

namespace Database\Seeders;

use App\Models\BreakItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BreakItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BreakItem::factory(15)->create(); 
    }
}
