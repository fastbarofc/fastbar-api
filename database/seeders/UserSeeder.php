<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Collaborator;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->delete();
        $userAdmin = User::factory()
            ->state([
                'name' => 'Admin',
                'email' => 'admin@gmail.com.br'
            ])
            ->create();

        $collaborator = Collaborator::factory()
            ->state([
                'name' => 'Admin',
                'user_id' => $userAdmin->id
            ])
            ->create();
            
        $roleAdmin = Role::where('name', 'Admin')->first();
        $userAdmin->assignRole($roleAdmin);

        $userClient = User::factory()
            ->state([
                'name' => 'Client',
                'email' => 'client@gmail.com.br'
            ])
            ->create();
        $roleClient = Role::where('name', 'Client')->first();
        $userClient->assignRole($roleClient);
    }
}
