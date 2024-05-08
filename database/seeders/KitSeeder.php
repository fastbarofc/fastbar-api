<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kit;

class KitSeeder extends Seeder
{
    
    public function run(): void
    {
        $grandparentKits = Kit::factory(15)->create();

        $grandparentKits = $grandparentKits->take(12);

        foreach ($grandparentKits as $parent) {
            Kit::factory(3)
            ->state(['kit_id'=> $parent->id])
            ->create();
        }

        $parentKits = Kit::whereNotNull('kit_id')->take(25);

        foreach ($parentKits as $parent) {
            Kit::factory(2)
            ->state(['kit_id'=> $parent->id])
            ->create();
        }
    }
}
