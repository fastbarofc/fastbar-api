<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Collaborator;
use App\Models\Qualification;
use App\Models\Address;

class CollaboratorSeeder extends Seeder
{
    public function run(): void
    {
        $collaborators = Collaborator::factory(10)->create();

        $qualifications = Qualification::all();

        $collaborators->each(function ($collaborator) use ($qualifications) {
            
            foreach ($qualifications as $qualification) {
                $collaborator->qualifications()->attach($qualification, [
                    'points' => rand(0, 5),
                ]);
            }

        });
        
        $collaborators = Collaborator::all()->random(5);

        $collaborators->each(function ($collaborator) {
            Address::factory()->count(1)->state([
                'addressable_id' => $collaborator->id,
                'addressable_type' => Collaborator::class,
            ])->create();
        });

    }
}
