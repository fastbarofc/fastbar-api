<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Collaborator;

class CollaboratorSeeder extends Seeder
{
    public function run(): void
    {
        Collaborator::factory(10)->create();
    }
}
