<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kit;

class KitSeeder extends Seeder
{
    
    public function run(): void
    {
        $kitParents = Kit::factory(3)->create();

        foreach ($kitParents as $parent) {
            Kit::factory(3)
            ->state(['kit_id'=> $parent->id])
            ->create();
        }
    }
}
