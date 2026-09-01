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
                'email' => 'admin@gmail.com.br',
                'password' => '456',
                'is_employee' => true,
            ])
            ->create();

        $collaborator = Collaborator::factory()
            ->state([
                'name' => 'Admin',
                'user_id' => $userAdmin->id,
                'has_login' => true,
            ])
            ->create();
            
        $roleAdmin = Role::where('name', 'Admin')->first();
        $userAdmin->assignRole($roleAdmin);
    }
}
