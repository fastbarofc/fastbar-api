<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\State;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    public function up(): void
    {
        
        $items = [
            [
                'id' => '1',
                'name' => 'Acre',
                'letter' => 'AC',
                'iso' => '12',
            ],
            [
                'id' => '2',
                'name' => 'Alagoas',
                'letter' => 'AL',
                'iso' => '27',
            ],
            [
                'id' => '3',
                'name' => 'Amazonas',
                'letter' => 'AM',
                'iso' => '13',
            ],
            [
                'id' => '4',
                'name' => 'Amapá',
                'letter' => 'AP',
                'iso' => '16',
            ],
            [
                'id' => '5',
                'name' => 'Bahia',
                'letter' => 'BA',
                'iso' => '29',
            ],
            [
                'id' => '6',
                'name' => 'Ceará',
                'letter' => 'CE',
                'iso' => '23',
            ],
            [
                'id' => '7',
                'name' => 'Distrito Federal',
                'letter' => 'DF',
                'iso' => '53',
            ],
            [
                'id' => '8',
                'name' => 'Espírito Santo',
                'letter' => 'ES',
                'iso' => '32',
            ],
            [
                'id' => '9',
                'name' => 'Goiás',
                'letter' => 'GO',
                'iso' => '52',
            ],
            [
                'id' => '10',
                'name' => 'Maranhão',
                'letter' => 'MA',
                'iso' => '21',
            ],
            [
                'id' => '11',
                'name' => 'Minas Gerais',
                'letter' => 'MG',
                'iso' => '31',
            ],
            [
                'id' => '12',
                'name' => 'Mato Grosso do Sul',
                'letter' => 'MS',
                'iso' => '50',
            ],
            [
                'id' => '13',
                'name' => 'Mato Grosso',
                'letter' => 'MT',
                'iso' => '51',
            ],
            [
                'id' => '14',
                'name' => 'Pará',
                'letter' => 'PA',
                'iso' => '15',
            ],
            [
                'id' => '15',
                'name' => 'Paraiba',
                'letter' => 'PB',
                'iso' => '25',
            ],
            [
                'id' => '16',
                'name' => 'Pernambuco',
                'letter' => 'PE',
                'iso' => '26',
            ],
            [
                'id' => '17',
                'name' => 'Piauí',
                'letter' => 'PI',
                'iso' => '22',
            ],
            [
                'id' => '18',
                'name' => 'Paraná',
                'letter' => 'PR',
                'iso' => '41',
            ],
            [
                'id' => '19',
                'name' => 'Rio de Janeiro',
                'letter' => 'RJ',
                'iso' => '33',
            ],
            [
                'id' => '20',
                'name' => 'Rio Grande do Norte',
                'letter' => 'RN',
                'iso' => '24',
            ],
            [
                'id' => '21',
                'name' => 'Rondônia',
                'letter' => 'RO',
                'iso' => '11',
            ],
            [
                'id' => '22',
                'name' => 'Roraima',
                'letter' => 'RR',
                'iso' => '14',
            ],
            [
                'id' => '23',
                'name' => 'Rio Grande do Sul',
                'letter' => 'RS',
                'iso' => '43',
            ],
            [
                'id' => '24',
                'name' => 'Santa Catarina',
                'letter' => 'SC',
                'iso' => '42',
            ],
            [
                'id' => '25',
                'name' => 'Sergipe',
                'letter' => 'SE',
                'iso' => '28',
            ],
            [
                'id' => '26',
                'name' => 'São Paulo',
                'letter' => 'SP',
                'iso' => '35',
            ],
            [
                'id' => '27',
                'name' => 'Tocantins',
                'letter' => 'TO',
                'iso' => '17',
            ],
        ];

        State::factory()->createMany($items);
    }

    
    public function down(): void
    {
        State::query()->delete();
    }
};
