<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\Company;
use App\Models\SectorOffice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // forceDelete porque email e unique e o model usa SoftDeletes:
        // um delete comum deixaria a linha e quebraria o proximo seed.
        User::query()->forceDelete();

        $roleAdmin = Role::where('name', 'Admin')->where('guard_name', 'api')->first();
        $companyIds = Company::pluck('id')->toArray();
        $sectorOffice = SectorOffice::query()->orderBy('id')->first();

        $accounts = [
            ['name' => 'TI', 'email' => 'ti@fastbarbr.com', 'password' => 'Fast#1979'],
            ['name' => 'Oficial', 'email' => 'oficial@fastbarbr.com', 'password' => '12345678'],
        ];

        foreach ($accounts as $account) {
            $user = User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $account['password'],
                'active' => true,
                'is_employee' => true,
            ]);

            // Collaborator::factory() cria um User extra quando o faker sorteia
            // has_login, entao aqui e create() direto.
            $collaborator = Collaborator::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'phone_1' => '00000000000',
                'sector_id' => $sectorOffice->sector_id,
                'sector_office_id' => $sectorOffice->id,
                'user_id' => $user->id,
                'has_login' => true,
                'active' => true,
            ]);

            $collaborator->companies()->sync($companyIds);

            $user->assignRole($roleAdmin);
        }
    }
}
