<?php

use App\Models\OfficeEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        OfficeEvent::create(['description' => 'Barman', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Carga', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Chapeiros', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Chef de Segurança', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => "Cheff's de cozinha", 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Churrasqueiros', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador de Bar Quente', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador de Copa', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador de Portaria', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador Geral', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador(es) de Massas ao Vivo', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Coordenador(es) de Mesa Temática', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Copeiros', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Cozinheiras', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Cumins (limpeza)', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçom a Caráter - Comida Japonesa', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçom exclusivo para noivos', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçons', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçons babá', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçons exclusivos para formandos', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Garçons Universitários', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Maitre(s)', 'daily' => '0', 'coordinator' => '1', 'date' => '0']);
        OfficeEvent::create(['description' => 'Motorista', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Porteiros', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Recepcionista', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Repositor de Bar Quente', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Repositores', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Repositores de Massas ao Vivo', 'daily' => '1', 'coordinator' => '0', 'date' => '1']);
        OfficeEvent::create(['description' => 'Salva Vidas', 'daily' => '1', 'coordinator' => '0', 'date' => '0']);
        OfficeEvent::create(['description' => 'Seguranças', 'daily' => '1', 'coordinator' => '1', 'date' => '1']);
        OfficeEvent::create(['description' => 'Tequileiros', 'daily' => '0', 'coordinator' => '0', 'date' => '0']);
    }

    public function down(): void
    {
        OfficeEvent::query()->delete();
    }
};
