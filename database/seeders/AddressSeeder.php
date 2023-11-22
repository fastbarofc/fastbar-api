<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            Address::factory()
                ->count(1)
                ->state([
                    'addressable_id' => $user->id,
                    'addressable_type' => User::class,
                ])
                ->create();
        }
    }
}
