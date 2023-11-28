<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Decorator;

class DecoratorSeeder extends Seeder
{
    public function run(): void
    {
        Decorator::factory(10)->create();
    }
}
