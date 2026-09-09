<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * A instalacao da FastBar opera com uma unica empresa. Nao ha factory aqui
     * de proposito: companies e cadastro de negocio, nao dado ficticio.
     */
    public function run(): void
    {
        Company::firstOrCreate(
            ['internal_name' => 'Fastbar'],
            [
                'legal_name' => 'Fastbar',
                'trade_name' => 'Fastbar',
                'type' => 'head_office',
            ]
        );
    }
}
