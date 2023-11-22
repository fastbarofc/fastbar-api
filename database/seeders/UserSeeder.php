<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->delete();
        User::factory()
            ->count(1)
            ->state([
                'name' => 'Admin',
                'email' => 'admin@gmail.com.br'
            ])
            ->create();
    }
}
