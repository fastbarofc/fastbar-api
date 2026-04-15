<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $costCenters = [
            ['code' => '1', 'description' => 'Administrativo',  'account' => 'analytic'],
            ['code' => '2', 'description' => 'Operacional',     'account' => 'analytic'],
            ['code' => '3', 'description' => 'Comercial',       'account' => 'analytic'],
            ['code' => '4', 'description' => 'Financeiro',      'account' => 'synthetic'],
        ];

        foreach ($costCenters as $data) {
            CostCenter::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
